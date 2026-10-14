<?php
session_start();
$correct_password = "101020"; 

// Supabase Configuration
$supabase_url = "https://hzcmrwztdamvojsypffp.supabase.co";
$supabase_key = "sb_publishable_SXOPOim6WzVkezP4B8cZRg_YcOKnPPT";
$bucket_name = "gallery"; 

if (isset($_GET['logout'])) { session_destroy(); header("Location: index.php"); exit; }

if (isset($_POST['login'])) {
    if ($_POST['login_pass'] === $correct_password) {$_SESSION['admin_mode'] = true;
        header("Location: index.php"); exit;
    }
}

// Updated function to fetch file list from Supabase Storage
function getSupabaseFiles($url,$key, $bucket) {$ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "$url/storage/v1/object/list/$bucket");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        "prefix" => "",
        "limit" => 100,
        "offset" => 0,
        "sortBy" => ["column" => "name", "order" => "desc"]
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $key",
        "apikey: $key",
        "Content-Type: application/json"
    ]);
    $result = curl_exec($ch);
    curl_close($ch);$files_data = json_decode($result, true);$media = [];
    if (is_array($files_data)) {
        foreach ($files_data as$file) {
            if (isset($file['name']) &&$file['name'] !== '.emptyFolderPlaceholder') {
                $media[] = "$url/storage/v1/object/public/$bucket/" . $file['name'];
            }
        }
    }
    return $media;
}

if (isset($_SESSION['admin_mode'])) {
    // Delete file from Supabase Storage
    if (isset($_GET['delete'])) {
        $file_url =$_GET['delete'];
        $file_name = basename($file_url);
        
        if (!empty($file_name)) {$ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "$supabase_url/storage/v1/object/$bucket_name/$file_name");
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer $supabase_key",
                "apikey: $supabase_key"
            ]);
            curl_exec($ch);
            curl_close($ch);
        }
        header("Location: index.php"); exit;
    }

    // Upload file directly to Supabase Storage
    if (isset($_POST['submit']) && !empty($_FILES["media"]["name"])) {
        $ext = strtolower(pathinfo($_FILES["media"]["name"], PATHINFO_EXTENSION));
        $unique_name = bin2hex(random_bytes(5)) . "_" . time() . "." . $ext;
        $tmp_name =$_FILES["media"]["tmp_name"];
        $mime_type =$_FILES["media"]["type"];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "$supabase_url/storage/v1/object/$bucket_name/$unique_name");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents($tmp_name));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $supabase_key",
            "apikey: $supabase_key",
            "Content-Type: $mime_type"
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            echo '<div style="background:red; color:white; padding:20px; font-family:sans-serif;">Curl error: ' . curl_error($ch) . '</div>';
            exit;
        }
        curl_close($ch);

        if ($http_code !== 200 &&$http_code !== 201) {
            echo '<div style="background:#161b2c; color:#ff5555; padding:30px; font-family:sans-serif; border: 2px solid red;">';
            echo '<h3>Upload Failed!</h3>';
            echo '<b>HTTP Code:</b> ' . $http_code . '<br><br>';
            echo '<b>Supabase Response:</b> <pre>' . htmlspecialchars($response) . '</pre><br>';
            echo '<a href="index.php" style="color:white; background:#6366f1; padding:10px 20px; text-decoration:none; border-radius:8px; display:inline-block;">Go Back</a>';
            echo '</div>';
            exit;
        }

        header("Location: index.php"); exit;
    }
}

$all_media = getSupabaseFiles($supabase_url, $supabase_key,$bucket_name);
$js_media = json_encode($all_media);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Krish Singh 🚀</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0b0f1a; --nav: rgba(11, 15, 26, 0.8); --card: #161b2c;
            --text: #ffffff; --accent: #6366f1; --border: rgba(255,255,255,0.08);
            --glass: rgba(255, 255, 255, 0.03);
        }
        body.light-mode {
            --bg: #f8fafc; --nav: rgba(248, 250, 252, 0.8); --card: #ffffff;
            --text: #0f172a; --border: rgba(0,0,0,0.05); --glass: rgba(0, 0, 0, 0.02);
        }
        * { box-sizing: border-box; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--bg); color: var(--text); margin: 0; padding: 0; overflow-x: hidden; }
        
        .navbar { padding: 18px 30px; display: flex; align-items: center; justify-content: space-between; backdrop-filter: blur(20px); background: var(--nav); border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 1000; }
        .brand { font-weight: 800; font-size: 18px; letter-spacing: -0.5px; color: var(--accent); }
        .icon-btn { cursor: pointer; background: var(--glass); border-radius: 12px; width: 42px; height: 42px; display: flex; align-items: center; justify-content: center; border: 1px solid var(--border); }
        
        .filter-tabs { display: flex; justify-content: center; gap: 10px; padding: 20px; background: var(--bg); position: sticky; top: 78px; z-index: 999; }
        .tab-btn { padding: 8px 20px; border-radius: 50px; border: 1px solid var(--border); background: var(--card); color: var(--text); cursor: pointer; font-size: 13px; font-weight: 600; }
        .tab-btn.active { background: var(--accent); color: white; border-color: var(--accent); }
        
        .sidebar { position: fixed; top: 0; left: -320px; width: 300px; height: 100%; background: var(--bg); border-right: 1px solid var(--border); z-index: 2000; overflow-y: auto; box-shadow: 20px 0 50px rgba(0,0,0,0.3); }
        .sidebar.active { left: 0; }
        .profile-box { padding: 50px 30px; text-align: center; background: linear-gradient(45deg, var(--accent), #8b5cf6); color: white; border-radius: 0 0 30px 30px; }
        .profile-img { width: 100px; height: 100px; border-radius: 35% 65% 60% 40% / 45% 45% 55% 55%; border: 4px solid rgba(255,255,255,0.2); object-fit: cover; margin-bottom: 15px; }
        
        .overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.4); backdrop-filter: blur(4px); z-index: 1500; }
        .overlay.active { display: block; }
        
        .gallery { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px; padding: 20px 30px; max-width: 1400px; margin: 0 auto; }
        .card { background: var(--card); border-radius: 24px; height: 280px; overflow: hidden; position: relative; border: 1px solid var(--border); cursor: pointer; }
        .card.hidden { display: none; }
        .card img, .card video { width: 100%; height: 100%; object-fit: cover; }
        
        .del-btn { position: absolute; top: 15px; right: 15px; background: rgba(239, 68, 68, 0.9); color: white; padding: 6px 12px; border-radius: 10px; font-size: 11px; text-decoration: none; z-index: 10; }
        .video-badge { position: absolute; bottom: 12px; left: 12px; background: rgba(0,0,0,0.5); color: white; padding: 4px 10px; border-radius: 8px; font-size: 10px; }
        
        #lightbox { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.95); z-index: 9999; flex-direction: column; backdrop-filter: blur(15px); }
        .img-container { flex: 1; display: flex; align-items: center; justify-content: center; padding: 20px; }
        #slider-img, #slider-vid { max-width: 95%; max-height: 85%; border-radius: 15px; display: none; }
        .close-btn { position: absolute; top: 25px; right: 30px; font-size: 30px; color: white; cursor: pointer; }
        
        .actions { padding: 25px; display: flex; gap: 12px; justify-content: center; align-items: center; background: linear-gradient(transparent, rgba(0,0,0,0.5)); }
        .nav-btn { background: rgba(255,255,255,0.08); color: white; width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; border-radius: 18px; cursor: pointer; }
        .action-btn { padding: 0 22px; height: 50px; border-radius: 18px; border: none; font-weight: 600; cursor: pointer; text-decoration: none; font-size: 13px; display: flex; align-items: center; justify-content: center; gap: 8px; color: white; background: var(--accent); }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="icon-btn" onclick="toggleSidebar()">☰</div>
        <div class="brand">Gallery Organised For 12-C1 🚀</div>
        <div class="icon-btn" onclick="toggleTheme()">☀️</div>
    </nav>

    <div class="filter-tabs">
        <button class="tab-btn active" onclick="filterMedia('all', this)">All</button>
        <button class="tab-btn" onclick="filterMedia('photo', this)">Photos</button>
        <button class="tab-btn" onclick="filterMedia('video', this)">Videos</button>
    </div>

    <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>
    
    <div class="sidebar" id="sidebar">
        <div class="profile-box">
            <img src="profile.jpg" alt="Profile" class="profile-img" onerror="this.style.display='none'">
            <div style="font-weight: 800; font-size: 20px;">Krish Singh</div>
            <div style="font-size: 11px; opacity: 0.8; text-transform: uppercase;">Programmer 💻</div>
        </div>

        <div style="padding: 30px;">
            <?php if (!isset($_SESSION['admin_mode'])): ?>
                <form method="POST">
                    <input type="password" name="login_pass" placeholder="Enter Admin Key" style="width:100%; padding:15px; border-radius:15px; border:1px solid var(--border); background:var(--card); color:var(--text); margin-bottom:15px;" required>
                    <button type="submit" name="login" class="action-btn" style="width:100%;">ACCESS DASHBOARD</button>
                </form>
            <?php else: ?>
                <form method="POST" enctype="multipart/form-data">
                    <input type="file" name="media" accept="image/*,video/*" required style="width:100%; margin-bottom:15px; color:var(--text);">
                    <button type="submit" name="submit" class="action-btn" style="width:100%;">UPLOAD MEDIA</button>
                </form>
                <a href="?logout=1" style="display:block; margin-top:40px; color:#ef4444; text-decoration:none; text-align:center; font-weight:600;">TERMINATE SESSION</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="gallery">
        <?php foreach($all_media as $index =>$file): 
            $ext = strtolower(pathinfo(parse_url($file, PHP_URL_PATH), PATHINFO_EXTENSION));
            $is_video = in_array($ext, ['mp4', 'webm', 'mov']);
        ?>
            <div class="card" data-type="<?= $is_video ? 'video' : 'photo' ?>" onclick="openSlider(<?= $index ?>)">
                <?php if (isset($_SESSION['admin_mode'])): ?>
                    <a href="?delete=<?= urlencode($file) ?>" class="del-btn" onclick="event.stopPropagation();">DELETE</a>
                <?php endif; ?>
                <?php if($is_video): ?>
                    <video src="<?= $file ?>#t=0.1" muted preload="metadata"></video>
                    <div class="video-badge">VIDEO</div>
                <?php else: ?>
                    <img src="<?= $file ?>" loading="lazy">
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <div id="lightbox">
        <span class="close-btn" onclick="closeSlider()">&times;</span>
        <div class="img-container">
            <img id="slider-img">
            <video id="slider-vid" controls preload="none"></video>
        </div>
        <div class="actions">
            <div class="nav-btn" onclick="changeImg(-1)">◀</div>
            <button class="action-btn" id="rot-btn" onclick="rotateImg()">ROTATE</button>
            <a id="download-link" href="#" download class="action-btn">SAVE</a>
            <div class="nav-btn" onclick="changeImg(1)">▶</div>
        </div>
    </div>

    <script>
        const media = <?= $js_media ?>;
        let current = 0;
        let rotation = 0;

        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('overlay').classList.toggle('active');
        }
        function toggleTheme() { document.body.classList.toggle('light-mode'); }
        
        function filterMedia(type, btn) {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            document.querySelectorAll('.card').forEach(card => {
                card.style.display = (type === 'all' || card.dataset.type === type) ? 'block' : 'none';
            });
        }

        function openSlider(index) {
            current = index;
            rotation = 0;
            updateSlider();
            document.getElementById('lightbox').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeSlider() {
            const vid = document.getElementById('slider-vid');
            vid.pause();
            vid.src = ""; 
            document.getElementById('lightbox').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        function changeImg(step) {
            current = (current + step + media.length) % media.length;
            rotation = 0;
            updateSlider();
        }

        function updateSlider() {
            const img = document.getElementById('slider-img');
            const vid = document.getElementById('slider-vid');
            const file = media[current];
            const isVideo = file.match(/\.(mp4|webm|mov)(\?.*)?$/i);

            img.style.display = vid.style.display = 'none';
            vid.pause();
            vid.src = "";

            if (isVideo) {
                vid.src = file;
                vid.style.display = 'block';
                vid.load();
                vid.play();
            } else {
                img.src = file;
                img.style.display = 'block';
                img.style.transform = `rotate(${rotation}deg)`;
            }
            document.getElementById('download-link').href = file;
        }

        function rotateImg() {
            rotation += 90;
            document.getElementById('slider-img').style.transform = `rotate(${rotation}deg)`;
        }
    </script>
</body>
</html>
