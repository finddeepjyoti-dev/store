<?php
ob_start();
require_once 'common/config.php';
if (isset($_GET['logout'])) { session_destroy(); header('Location: login.php'); exit; }
if (isset($_SESSION['user_id'])) header('Location: index.php');

// ---------- Ensure required columns exist ----------
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS firebase_uid VARCHAR(128) NULL AFTER password");
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_pic VARCHAR(255) NULL AFTER firebase_uid");

// AJAX handling – only Google Sign‑In remains
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];

    if ($_POST['type'] === 'google_login') {
        $idToken = trim($_POST['idtoken'] ?? '');

        // Verify the token via Firebase REST API
        $apiKey = 'AIzaSyBl8IxjM3_gTwcvGBUIsnZwDuQDGM2kh_8';
        $verifyUrl = 'https://identitytoolkit.googleapis.com/v1/accounts:lookup?key=' . $apiKey;
        $postData = json_encode(['idToken' => $idToken]);

        // Try cURL first, fallback to file_get_contents
        $resultJson = '';
        if (function_exists('curl_init')) {
            $ch = curl_init($verifyUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $resultJson = curl_exec($ch);
            if (curl_errno($ch)) {
                $response['message'] = 'cURL error: ' . curl_error($ch);
                echo json_encode($response);
                curl_close($ch);
                exit;
            }
            curl_close($ch);
        } else {
            // Fallback to stream context
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/json\r\n",
                    'content' => $postData,
                    'timeout' => 10
                ]
            ]);
            $resultJson = @file_get_contents($verifyUrl, false, $context);
        }

        if (!$resultJson) {
            $response['message'] = 'Unable to verify Google token (network issue).';
            echo json_encode($response);
            exit;
        }

        $data = json_decode($resultJson, true);
        if (!isset($data['users'][0])) {
            $response['message'] = 'Google token verification failed. Token may be invalid.';
            echo json_encode($response);
            exit;
        }

        $userInfo    = $data['users'][0];
        $email       = $userInfo['email'];
        $name        = $userInfo['displayName'] ?? 'Google User';
        $firebaseUid = $userInfo['localId'];
        $photoUrl    = $userInfo['photoUrl'] ?? '';

        // Check if user already exists by firebase_uid
        $user = $conn->query("SELECT id FROM users WHERE firebase_uid = '".$conn->real_escape_string($firebaseUid)."'")->fetch_assoc();

        if ($user) {
            // Update profile pic
            $conn->query("UPDATE users SET profile_pic = '".$conn->real_escape_string($photoUrl)."' WHERE id = {$user['id']}");
            $_SESSION['user_id'] = $user['id'];
            $response = ['success' => true, 'redirect' => 'index.php'];
        } else {
            // Check if email exists (manual account)
            $existing = $conn->query("SELECT id, firebase_uid FROM users WHERE email = '".$conn->real_escape_string($email)."'")->fetch_assoc();
            if ($existing) {
                if (empty($existing['firebase_uid'])) {
                    $conn->query("UPDATE users SET firebase_uid = '".$conn->real_escape_string($firebaseUid)."', profile_pic = '".$conn->real_escape_string($photoUrl)."' WHERE id = {$existing['id']}");
                    $_SESSION['user_id'] = $existing['id'];
                    $response = ['success' => true, 'redirect' => 'index.php'];
                } else {
                    $response['message'] = 'This email is already registered with a different Google account.';
                }
            } else {
                // Create a new user
                $conn->query("INSERT INTO users (name, phone, email, password, firebase_uid, profile_pic) VALUES ('".$conn->real_escape_string($name)."', '', '".$conn->real_escape_string($email)."', '', '".$conn->real_escape_string($firebaseUid)."', '".$conn->real_escape_string($photoUrl)."')");
                $_SESSION['user_id'] = $conn->insert_id;
                $response = ['success' => true, 'redirect' => 'index.php'];
            }
        }
    }

    echo json_encode($response);
    exit;
}
?>
<?php include 'common/header.php'; ?>
<?php include 'common/sidebar.php'; ?>

<main class="w-full flex items-center justify-center px-4 py-10 bg-rose-50/30 min-h-screen">
    <div class="w-full max-w-md mx-auto">
        <div class="bg-white border border-rose-200/60 rounded-3xl shadow-sm p-8 lg:p-10 text-center">
            <div class="inline-flex items-center justify-center w-20 h-20 lg:w-24 lg:h-24 rounded-full bg-gradient-to-br from-rose-400 to-pink-400 shadow-lg shadow-rose-200/50 mb-6">
                <i class="fas fa-shopping-bag text-3xl lg:text-4xl text-white"></i>
            </div>
            <h1 class="font-script text-3xl lg:text-4xl font-bold text-rose-600 mb-2">BloomBouquet</h1>
            <p class="text-gray-500 text-base lg:text-lg mb-8">Sign in to continue</p>

            <!-- Google Sign-In button -->
            <button id="googleSignInBtn" onclick="startGoogleSignIn()"
                    class="w-full bg-white hover:bg-gray-50 text-gray-700 font-semibold py-3 px-4 rounded-2xl border border-rose-200 shadow-sm transition active:scale-95 flex items-center justify-center gap-2">
                <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" alt="Google" class="w-5 h-5">
                Sign in with Google
            </button>
            <p id="googleMsg" class="text-red-500 text-sm text-center font-medium mt-3 hidden"></p>

            <!-- Key features -->
            <div class="mt-10 text-gray-500 text-sm space-y-2">
                <p><i class="fas fa-check-circle text-rose-500 mr-1"></i> Fast delivery</p>
                <p><i class="fas fa-check-circle text-rose-500 mr-1"></i> Secure payments</p>
                <p><i class="fas fa-check-circle text-rose-500 mr-1"></i> 24/7 support</p>
            </div>
        </div>
    </div>
</main>

<?php include 'common/bottom.php'; ?>

<!-- Firebase & Google Sign‑In -->
<script src="https://www.gstatic.com/firebasejs/9.22.2/firebase-app-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/9.22.2/firebase-auth-compat.js"></script>
<script>
const firebaseConfig = {
  apiKey: "AIzaSyBl8IxjM3_gTwcvGBUIsnZwDuQDGM2kh_8",
  authDomain: "updatesall-f3c0a.firebaseapp.com",
  projectId: "updatesall-f3c0a",
  storageBucket: "updatesall-f3c0a.firebasestorage.app",
  messagingSenderId: "812548723284",
  appId: "1:812548723284:web:eecd381dc399f0f11693c1"
};

firebase.initializeApp(firebaseConfig);
const firebaseAuth = firebase.auth();
const googleProvider = new firebase.auth.GoogleAuthProvider();

function startGoogleSignIn() {
    firebaseAuth.signInWithPopup(googleProvider)
        .then(result => {
            const user = result.user;
            user.getIdToken().then(idToken => {
                const fd = new FormData();
                fd.append('ajax', '1');
                fd.append('type', 'google_login');
                fd.append('idtoken', idToken);

                showLoader();
                fetch('login.php', { method: 'POST', body: fd })
                    .then(res => res.json())
                    .then(data => {
                        hideLoader();
                        if (data.success) {
                            window.location = data.redirect;
                        } else {
                            const msg = document.getElementById('googleMsg');
                            msg.textContent = data.message || 'Google sign‑in failed.';
                            msg.classList.remove('hidden');
                        }
                    })
                    .catch(err => {
                        hideLoader();
                        console.error(err);
                        const msg = document.getElementById('googleMsg');
                        msg.textContent = 'Network error during login.';
                        msg.classList.remove('hidden');
                    });
            });
        })
        .catch(error => {
            console.error(error);
            const msg = document.getElementById('googleMsg');
            msg.textContent = error.message || 'Sign‑in cancelled.';
            msg.classList.remove('hidden');
        });
}
</script>
</body>
</html>