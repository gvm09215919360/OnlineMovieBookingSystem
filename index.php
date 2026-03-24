<?php
include('connect.php');
include('header.php');

// Start session if not already started
if(session_status() === PHP_SESSION_NONE) session_start();

$new_user_modal = false;
$uid = $_SESSION['uid'] ?? null;

// ----------------------------
// 1. Check User Status
if($uid){
    // Use Prepared Statement for security
    $stmt = $con->prepare("SELECT verified FROM users WHERE userid = ? LIMIT 1");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $userResult = $stmt->get_result();
    $userData = mysqli_fetch_assoc($userResult);

    if($userData && $userData['verified'] == 1){
        // Check if user has selected genres
        $stmt = $con->prepare("SELECT * FROM user_genres WHERE userid = ? LIMIT 1");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        $checkGenres = $stmt->get_result();
        
        if(mysqli_num_rows($checkGenres) == 0){
            $new_user_modal = true; // Show genre selection modal
        }
    } else {
        // Optional: force logout if not verified
        session_destroy();
        header("Location: login.php");
        exit;
    }
    $stmt->close();
}

// ----------------------------
// 2. Fetch All Genres
$genreQuery = mysqli_query($con, "SELECT * FROM categories ORDER BY catname ASC");
$allGenres = [];
while($row = mysqli_fetch_assoc($genreQuery)) $allGenres[] = $row;

// ----------------------------
// 3. HERO MOVIE (Optimized)
$heroQuery = mysqli_query($con,"SELECT * FROM movies ORDER BY RAND() LIMIT 5");
$heroMovies = [];
while($row = mysqli_fetch_assoc($heroQuery)){
    $heroMovies[] = $row;
}
$hero = $heroMovies[0] ?? null;

// ----------------------------
// 4. Recommended Movies
$recommendedMovies = [];
if($uid){
    $stmt = $con->prepare("SELECT catid FROM user_genres WHERE userid = ?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $res = $stmt->get_result();
    
    $genreIds = [];
    while($r = mysqli_fetch_assoc($res)) $genreIds[] = $r['catid'];
    $stmt->close();

    if(!empty($genreIds)){
        $placeholders = implode(',', array_fill(0, count($genreIds), '?'));
        $stmt = $con->prepare("SELECT * FROM movies WHERE catid IN ($placeholders) ORDER BY RAND() LIMIT 4");
        $types = str_repeat('i', count($genreIds));
        $stmt->bind_param($types, ...$genreIds);
        $stmt->execute();
        $recResult = $stmt->get_result();
        
        while($row = mysqli_fetch_assoc($recResult)){
            $recommendedMovies[] = $row;
        }
        $stmt->close();
    }
}

// ----------------------------
// 5. NOW SHOWING
$nowShowing = mysqli_query($con,"SELECT * FROM movies ORDER BY movieid DESC LIMIT 4");

// ----------------------------
// 6. TRENDING
$trending = mysqli_query($con,"SELECT * FROM trending_movies ORDER BY RAND() LIMIT 4");
?>

<style>
body{ background:#0F173D; font-family:Poppins; color:white; margin:0; }
.hero{position:relative;height:85vh;display:flex;align-items:center;padding-left:80px;overflow:hidden;}
.hero img{position:absolute;width:100%;height:100%;object-fit:cover;filter:brightness(60%);top:0;left:0;z-index: 0;}
.hero-content{position:relative;max-width:600px;z-index:2;}
.hero h1{font-size:64px;font-weight:800;}
.hero p{opacity:.8;}
.hero-buttons{margin-top:20px;}
.hero::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    width: 100%; /* controls how far the dark overlay goes */
    height: 100%;
    z-index: 1;

    /* smooth dark gradient from left → transparent */
    background: linear-gradient(
        to right,
        rgba(0, 0, 0, 0.85) 0%,
        rgba(0, 0, 0, 0.65) 30%,
        rgba(0, 0, 0, 0.3) 60%,
        rgba(0, 0, 0, 0) 100%
    );
}
.btn-main{background:#A8C6D8;border:none;padding:10px 30px;border-radius:30px;font-weight:600;margin-right:10px;cursor:pointer;text-decoration:none;color:#000;}
.btn-main:hover{background:white;}
.search-box{position:absolute;top:20px;right:40px;}
.search-box input{border-radius:20px;border:none;padding:8px 15px;}
.section{padding:60px 80px;}
.section h2{font-size:64px;font-weight:800;color:#ffffff;margin-bottom:30px;}
.movies{display:grid;grid-template-columns:repeat(4,1fr);gap:30px;}
.movie-card{text-align:center;}
.movie-card img{width:100%;height:600px;object-fit:cover;border-radius:15px;}
.movie-card button{margin-top:10px;border:none;background:#A8C6D8;padding:6px 18px;border-radius:20px;font-weight:600;cursor:pointer;}
.movie-card button:hover{background:white;}

.search-box {
    position: absolute;
    top: 20px;
    right: 40px;
}

#searchForm {
    position: relative;
    display: flex;
    align-items: center;
}

/* Input initially collapsed */
#searchInput {
    width: 0;
    padding: 8px 35px 8px 15px; /* right padding for icon */
    border-radius: 20px;
    border: none;
    outline: none;
    background: rgba(255,255,255,0.1);
    color: white;
    opacity: 0;
    transition: width 0.3s ease, opacity 0.3s ease;
}

/* Input when active */
#searchForm.active #searchInput {
    width: 200px;
    opacity: 1;
}

/* SVG icon inside input */
#searchIcon {
    position: absolute;
    right: 10px;
    cursor: pointer;
    pointer-events: auto;
    transition: transform 0.2s;
}

/* Optional click animation */
#searchIcon:active {
    transform: scale(0.9);
}

/* Input text placeholder color */
#searchInput::placeholder {
    color: rgba(255,255,255,0.7);
}

/* Modal styles */
/* Modal Background */
.modal-bg {
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(15, 23, 61, 0.6); /* semi-transparent overlay */
    backdrop-filter: blur(5px);
    justify-content: center;
    align-items: center;
    z-index: 9999;
    display: none;
}

/* Modal Container */
.modal {
    background: rgba(15, 23, 61, 0.6); /* semi-transparent dark */
    color: #fff;
    border-radius: 20px;
    padding: 40px 30px;
    width: 500px;
    max-width: 90%;
    display: flex;
    flex-direction: column;
    align-items: center;
    box-shadow: 0 8px 30px rgba(0,0,0,0.7);
    backdrop-filter: blur(10px); /* frosty glass effect */
    border: 1px solid rgba(255,255,255,0.1); /* subtle glass border */
    animation: modalFadeIn 0.4s ease-out;
}

/* Modal Animation */
@keyframes modalFadeIn {
    from { opacity: 0; transform: translateY(-50px);}
    to { opacity: 1; transform: translateY(0);}
}

/* Modal Header */
.modal h3 {
    font-size: 28px;
    font-weight: 700;
    text-align: center;
    margin-bottom: 25px;
}

/* -----------------------------
MODERN PILL-STYLE GENRE CHECKBOXES
----------------------------- */

/* Modal form grid */
.modal form {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 15px;
    justify-items: center;
    align-items: center;
    padding: 10px 0;
}

/* Label acts as the pill button */
.modal label {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 20px;
    border-radius: 25px; /* pill shape */
    cursor: pointer;
    user-select: none;
    text-align: center;
    width: 100%;
    max-width: 160px;
    background: rgba(255, 255, 255, 0.05);
    color: #fff;
    font-weight: 600;
    transition: all 0.2s ease;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

/* Hover effect */
.modal label:hover {
    background: rgba(255, 255, 255, 0.15);
    transform: translateY(-2px);
}

/* Hide default checkbox */
.modal input[type="checkbox"] {
    display: none;
}

/* Checked state - highlighted pill */
.modal input[type="checkbox"]:checked + span {
    background: #A8C6D8;
    color: #0F173D;
    border-color: #A8C6D8;
    box-shadow: 0 4px 15px rgba(168,198,216,0.5);
}

/* Span inside label for text */
.modal label span {
    width: 100%;
    text-align: center;
    padding: 6px 0;
    border-radius: 20px;
    transition: all 0.2s ease;
    display: inline-block;
}

/* Optional: add smooth scaling on select */
.modal input[type="checkbox"]:checked + span {
    transform: scale(1.05);
}

/* Submit Button */
/* FULLSCREEN MODAL BACKGROUND */
.modal-bg {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(15, 23, 61, 0.95);
    backdrop-filter: blur(5px);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

/* FULLSCREEN MODAL CONTAINER */
.modal {
    width: 100%;
    height: 100%;
    padding: 40px 50px;
    background: rgba(15, 23, 61, 0.95);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-start;
    overflow-y: auto;
    text-align: center;
}

/* Modal Header */
.modal h3 {
    font-size: 36px;
    font-weight: 800;
    margin-bottom: 40px;
    color: #fff;
}

/* Modal form grid for genres */
/* Modal form grid for genres */
.modal form {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 25px;
    justify-items: center;
    align-items: center;
    width: 100%;
    margin-bottom: 30px; /* space before submit button */
}

/* Submit Button directly after genres */
.modal button {
    padding: 16px 50px;
    border: none;
    border-radius: 30px;
    background: #A8C6D8;
    color: #0F173D;
    font-weight: 700;
    font-size: 18px;
    cursor: pointer;
    transition: all 0.2s ease;
    display: block;
    margin: 0 auto; /* center horizontally */
}

.modal button:hover {
    background: #C0D6E4;
    transform: scale(1.05);
}

/* Genre buttons - same size */
.modal label {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 14px 0;
    border-radius: 30px;
    cursor: pointer;
    user-select: none;
    text-align: center;
    width: 160px;
    background: rgba(255, 255, 255, 0.05);
    color: #fff;
    font-weight: 600;
    transition: all 0.2s ease;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.modal label span {
    width: 100%;
    text-align: center;
    display: inline-block;
}

.modal input[type="checkbox"]:checked + span {
    background: #A8C6D8;
    color: #0F173D;
    border-color: #A8C6D8;
    box-shadow: 0 4px 15px rgba(168,198,216,0.5);
    transform: scale(1.05);
}

.modal label span {
    width: 100%;
    text-align: center;
    display: inline-block;
}

/* Submit Button fixed at bottom */
.modal button {
    margin-top: 20px;
    padding: 16px 50px;
    border: none;
    border-radius: 30px;
    background: #A8C6D8;
    color: #0F173D;
    font-weight: 700;
    font-size: 18px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.modal button:hover {
    background: #C0D6E4;
    transform: scale(1.05);
}

/* Make form stretch to push button down */
.modal form {
    flex-grow: 1;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 25px;
    justify-items: center;
    align-items: center;
    width: 100%;
}
/* MOVIE DETAILS MODAL */
.movie-modal{
position:fixed;
top:0;
left:0;
width:100%;
height:100%;
background:rgba(0,0,0,0.85);
display:none;
justify-content:center;
align-items:center;
z-index:9999;
}

.movie-modal-content{
background:linear-gradient(160deg,#0F173D,#1F4E79);
padding:30px;
border-radius:15px;
color:white;
max-width:600px;
width:90%;
position:relative;
box-shadow:0 0 25px rgba(120,180,255,0.6);
}

.movie-modal-close{
position:absolute;
top:10px;
right:15px;
font-size:28px;
cursor:pointer;
}

.movie-trailer video{
width:100%;
border-radius:8px;
margin-top:15px;
}
</style>

<!-- HERO -->
<?php if($hero): ?>
<section class="hero">
    <img src="admin/uploads/<?php echo $hero['image']; ?>">
<div class="search-box">
    <form method="POST" id="searchForm">
        <input type="text" name="search" placeholder="Search..." id="searchInput">
        <svg id="searchIcon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="white" viewBox="0 0 24 24">
            <path d="M21.707 20.293l-4.387-4.387a8 8 0 10-1.414 1.414l4.387 4.387a1 1 0 001.414-1.414zM10 16a6 6 0 110-12 6 6 0 010 12z"/>
        </svg>
    </form>
</div>
    <div class="hero-content">
        <h1><?php echo $hero['title']; ?></h1>
        <p><?php echo substr($hero['description'],0,150); ?>...</p>
        <div class="hero-buttons">
            <a href="booking.php?movie_id=<?php echo $hero['movieid']; ?>" class="btn-main">Buy Tickets</a>
            <?php if($hero['trailer']!=""){ ?>
            <a href="admin/uploads/<?php echo $hero['trailer']; ?>" target="_blank">
                <button class="btn-main">Trailer</button>
            </a>
            <?php } ?>
        </div>
    </div>
</section>

<script>
const searchForm = document.getElementById('searchForm');
const searchInput = document.getElementById('searchInput');
const searchIcon = document.getElementById('searchIcon');

// Toggle search input on icon click
searchIcon.addEventListener('click', () => {
    searchForm.classList.toggle('active');
    if(searchForm.classList.contains('active')){
        searchInput.focus();
    }
});

// Submit form when Enter is pressed
searchInput.addEventListener('keypress', function(e){
    if(e.key === 'Enter'){
        searchForm.submit();
    }
});

// Optional: collapse search input when clicking outside
document.addEventListener('click', function(e){
    if(!searchForm.contains(e.target)){
        searchForm.classList.remove('active');
    }
});
let heroImages = [];
let heroTitles = [];
let heroDescriptions = [];
<?php
foreach($heroMovies as $row){
    $image = 'admin/uploads/'.$row['image'];
    $title = addslashes($row['title']);
    $desc = addslashes(substr($row['description'],0,150));
    echo "heroImages.push('$image');\n";
    echo "heroTitles.push('$title');\n";
    echo "heroDescriptions.push('$desc');\n";
}
?>
let currentHero = 0;
const heroImg = document.querySelector('.hero img');
const heroTitle = document.querySelector('.hero h1');
const heroDesc = document.querySelector('.hero p');
function nextHero() {
    currentHero = (currentHero + 1) % heroImages.length;
    heroImg.src = heroImages[currentHero];
    heroTitle.textContent = heroTitles[currentHero];
    heroDesc.textContent = heroDescriptions[currentHero] + '...';
}
setInterval(nextHero, 3000);
</script>
<?php endif; ?>

<!-- RECOMMENDED MOVIES -->
<?php if(!empty($recommendedMovies)): ?>
<section class="section">
    <h2>Recommended for You</h2>
    <div class="movies">
        <?php foreach($recommendedMovies as $row): ?>
        <div class="movie-card">
            <img src="admin/uploads/<?php echo $row['image']; ?>" class="movie-poster">
            <h4><?php echo htmlspecialchars($row['title']); ?></h4>
<button class="view-details"
data-title="<?php echo htmlspecialchars($row['title']); ?>"
data-genre=""
data-rating="<?php echo $row['rating'] ?? 'N/A'; ?>"
data-description="<?php echo htmlspecialchars($row['description']); ?>"
data-trailer="<?php echo $row['trailer']; ?>">
View Details
</button>
        </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- NOW SHOWING -->
<section class="section">
<h2>Now Showing</h2>
<div class="movies">
<?php 
if($nowShowing && mysqli_num_rows($nowShowing) > 0){
    while($row=mysqli_fetch_assoc($nowShowing)){ 
?>
<div class="movie-card">
<img src="admin/uploads/<?php echo $row['image']; ?>" class="movie-poster">
<h4><?php echo $row['title']; ?></h4>
<button class="view-details"
data-title="<?php echo htmlspecialchars($row['title']); ?>"
data-genre=""
data-rating="<?php echo $row['rating'] ?? 'N/A'; ?>"
data-description="<?php echo htmlspecialchars($row['description']); ?>"
data-trailer="<?php echo $row['trailer']; ?>">
View Details
</button>
</div>
<?php } } ?>
</div>
</section>

<!-- TRENDING -->
<section class="section">
<h2>Trending Movies</h2>
<div class="movies">
<?php 
if($trending && mysqli_num_rows($trending) > 0){
    while($row=mysqli_fetch_assoc($trending)){ 
?>
<div class="movie-card">
<img src="admin/uploads/<?php echo $row['image']; ?>">
<h4><?php echo $row['title']; ?></h4>
<button class="view-details"
data-title="<?php echo htmlspecialchars($row['title']); ?>"
data-genre=""
data-rating="<?php echo $row['rating'] ?? 'N/A'; ?>"
data-description="<?php echo htmlspecialchars($row['description']); ?>"
data-trailer="<?php echo $row['trailer']; ?>">
View Details
</button>
</div>
<?php } } ?>
</div>
</section>

<!-- NEW USER GENRE MODAL -->
<?php if($new_user_modal): ?>
<div class="modal-bg" id="genreModal">
    <div class="modal">
        <h3>Select Your Favorite Genres</h3>
        <form method="POST" action="save_genres.php">
            <?php foreach($allGenres as $genre): ?>
            <label>
                <input type="checkbox" name="genres[]" value="<?= $genre['catid'] ?>">
                <span><?= htmlspecialchars($genre['catname']) ?></span>
            </label>
            <?php endforeach; ?>
            <input type="hidden" name="userid" value="<?= $uid ?>">
            <button type="submit">Save Preferences</button>
        </form>
    </div>
</div>
<?php endif; ?>


<!-- MOVIE DETAILS MODAL -->
<div id="movieModal" class="movie-modal">
  <div class="movie-modal-content">
    <span class="movie-modal-close">&times;</span>
    <div id="movie-modal-body"></div>
  </div>
</div>


<script>

document.addEventListener("DOMContentLoaded", function(){

/* -------------------------
MOVIE DETAILS MODAL
------------------------- */

const movieModal = document.getElementById("movieModal");
const modalBody = document.getElementById("movie-modal-body");
const closeBtn = document.querySelector(".movie-modal-close");

document.addEventListener("click", function(e){

if(e.target.classList.contains("view-details")){

let title = e.target.dataset.title || "";
let genre = e.target.dataset.genre || "";
let rating = e.target.dataset.rating || "N/A";
let description = e.target.dataset.description || "";
let trailer = e.target.dataset.trailer || "";

let trailerHTML = "";

if(trailer !== ""){
trailerHTML = `
<div class="movie-trailer">
<video controls>
<source src="admin/uploads/${trailer}" type="video/mp4">
</video>
</div>
`;
}else{
trailerHTML = `<p><em>Trailer not available</em></p>`;
}

modalBody.innerHTML = `
<h2>${title}</h2>
<p><strong>Genre:</strong> ${genre}</p>
<p><strong>Rating:</strong> ${rating}</p>
<p><strong>Description:</strong><br>${description}</p>
${trailerHTML}
`;

movieModal.style.display = "flex";

}

});


closeBtn.onclick = function(){
movieModal.style.display = "none";
}

window.onclick = function(e){
if(e.target === movieModal){
movieModal.style.display = "none";
}
}


/* -------------------------
GENRE MODAL
------------------------- */

const genreModal = document.getElementById("genreModal");

if(genreModal){
genreModal.style.display = "flex";

genreModal.addEventListener("click", function(e){
if(e.target === genreModal){
genreModal.style.display = "none";
}
});
}

});

</script>

<!-- ✅ PWA SERVICE WORKER REGISTRATION -->
<script>
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/OnlineMovieBookingSystem---PHP-MYSQL-main/service-worker.js')
      .then(() => console.log('Service Worker registered ✅'))
      .catch(err => console.log('Service Worker error:', err));
  }
</script>

<?php include('footer.php'); ?>
