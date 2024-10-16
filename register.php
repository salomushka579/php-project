<?php
session_start();

// Redirect to dashboard if user is already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

// Include necessary files
require __DIR__ . '/env_loader.php';
loadEnv(__DIR__ . '/.env'); // Load .env file for database connection and email settings

// Database connection
try {
    $dsn = "mysql:host=" . $_ENV['DB_HOST'] . ";dbname=" . $_ENV['DB_NAME'] . ";port=" . $_ENV['DB_PORT'];
    $pdo = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASS']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if the users table exists; if not, create it
    $createTableSQL = "CREATE TABLE IF NOT EXISTS users (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        firstname VARCHAR(50) NOT NULL,
        lastname VARCHAR(50) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    $pdo->exec($createTableSQL);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get input values and validate
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Validation
    $errors = [];
    if (empty($firstname) || empty($lastname) || empty($email) || empty($password)) {
        $errors[] = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    } else {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $errors[] = "Email already exists.";
        }
    }

    if (empty($errors)) {
        // Hash the password
        $hashedPassword = hash('sha256', $password);

        // Insert into the database
        $sql = "INSERT INTO users (firstname, lastname, email, password) VALUES (:firstname, :lastname, :email, :password)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':firstname' => $firstname,
            ':lastname'  => $lastname,
            ':email'     => $email,
            ':password'  => $hashedPassword
        ]);

        // Send a confirmation email to the user
        $loginUrl = $_ENV['APP_URL'] . '/login.php';
        $subject = "Registration Successful";
        $message = "
            <html>
            <head>
                <title>Registration Successful</title>
            </head>
            <body>
                <p>Hello $firstname.' '.$lastname,</p>
                <p>Thank you for registering on our site.</p>
                <p>Click the link below to login:</p>
                <a href='$loginUrl'>Login Here</a>
            </body>
            </html>
        ";

        // Email headers
        $headers = "From: " . $_ENV['MAIL_FROM'] . "\r\n";
        $headers .= "Reply-To: " . $_ENV['MAIL_REPLY'] . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

        // Send email
        if (mail($email, $subject, $message, $headers)) {
            echo "<div class='alert alert-success'>Registration successful. A confirmation email has been sent to your email.</div>";
        } else {
            echo "<div class='alert alert-warning'>Registration successful, but failed to send confirmation email.</div>";
        }
    } else {
        // Display validation errors
        foreach ($errors as $error) {
            echo "<div class='alert alert-danger'>$error</div>";
        }
    }
}
?>

<!-- HTML Form for Registration -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Registration</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
<div class="container mt-5">
    <h2>User Registration</h2>
  <form id="registrationForm" method="POST" action="register.php">
    <div class="form-group">
      <label for="firstname">First Name:</label>
      <input type="text" class="form-control <?php echo empty($firstname) && $_SERVER["REQUEST_METHOD"] == "POST" ? 'is-invalid' : ''; ?>" id="firstname" name="firstname" value="<?php echo isset($firstname) ? $firstname : null; ?>" required>
    </div>
    <div class="form-group">
      <label for="lastname">Last Name:</label>
      <input type="text" class="form-control <?php echo empty($lastname) && $_SERVER["REQUEST_METHOD"] == "POST" ? 'is-invalid' : ''; ?>" id="lastname" name="lastname" value="<?php echo isset($lastname) ? $lastname : null; ?>" required>
    </div>
    <div class="form-group">
      <label for="email">Email:</label>
      <input type="email" class="form-control <?php echo empty($email) && $_SERVER["REQUEST_METHOD"] == "POST" ? 'is-invalid' : ''; ?>" id="email" name="email" value="<?php echo isset($email) ? $email : null; ?>" required>
    </div>
    <div class="form-group">
      <label for="password">Password:</label>
      <input type="password" class="form-control <?php echo empty($password) && $_SERVER["REQUEST_METHOD"] == "POST" ? 'is-invalid' : ''; ?>" id="password" name="password" required>
    </div>
    <button type="submit" class="btn btn-primary mt-3">Register</button>
  </form>
    <p class="mt-3">Already have an account? <a href="login.php">Login here</a>.</p>
</div>

<script>
  $(document).ready(function() {
    $("#registrationForm").on("submit", function(e) {
      let isValid = true;
      $(".alert").remove(); // Remove previous alerts

      // Client-side validation
      const firstname = $("#firstname").val();
      const lastname = $("#lastname").val();
      const email = $("#email").val();
      const password = $("#password").val();

      if (!firstname || !lastname || !email || !password) {
        isValid = false;
        $('input').each(function() {
          if ($(this).val() === '') {
            $(this).addClass('is-invalid');
          } else {
            $(this).removeClass('is-invalid');
          }
        });
        $(this).prepend("<div class='alert alert-danger'>All fields are required.</div>");
      }

      if (password.length < 8 && password.length > 0) {
        isValid = false;
        $(this).prepend("<div class='alert alert-danger'>Password must be at least 8 characters long.</div>");
      }

      if (!validateEmail(email) && email.length > 0) {
        isValid = false;
        $(this).prepend("<div class='alert alert-danger'>Invalid email format.</div>");
      }

      if (!isValid) {
        e.preventDefault(); // Prevent form submission if validation fails
      }
    });

    function validateEmail(email) {
      const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      return re.test(String(email).toLowerCase());
    }
  });
</script>
</body>
</html>
