<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';

$errors = [];
$success = '';
$fullName = '';
$email = '';
$userCount = 0;

try {
    $userCount = (int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn();
} catch (Throwable $exception) {
    $errors[] = 'Database connection failed. Import database/schema.sql first and verify credentials in config/database.php.';
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && empty($errors)) {
    if ($userCount > 0) {
        $errors[] = 'Setup is locked because at least one user already exists.';
    } else {
        $fullName = trim((string) ($_POST['full_name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($fullName === '') {
            $errors[] = 'Full name is required.';
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid email is required.';
        }

        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }

        if (empty($errors)) {
            $stmt = db()->prepare(
                'INSERT INTO users (full_name, email, password_hash, role, is_active) VALUES (:full_name, :email, :password_hash, :role, :is_active)'
            );

            $stmt->execute([
                'full_name' => $fullName,
                'email' => strtolower($email),
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'role' => 'admin',
                'is_active' => 1,
            ]);

            $success = 'Admin account created. You can now log in from the login page.';
            $userCount = 1;
        }
    }
}

?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Portfolio Setup</title>
    <style>
      :root {
        color-scheme: light dark;
      }

      body {
        margin: 0;
        min-height: 100vh;
        display: grid;
        place-items: center;
        font-family: "Segoe UI", Tahoma, sans-serif;
        background: #0f1c18;
        color: #f3faf6;
        padding: 1rem;
      }

      .card {
        width: min(100%, 460px);
        border-radius: 14px;
        border: 1px solid rgba(255, 255, 255, 0.2);
        padding: 1.2rem;
        background: rgba(9, 20, 16, 0.7);
      }

      h1 {
        margin-top: 0;
        margin-bottom: 0.4rem;
      }

      p {
        margin-top: 0;
        opacity: 0.9;
      }

      label {
        display: block;
        margin-top: 0.7rem;
        margin-bottom: 0.2rem;
      }

      input {
        width: 100%;
        box-sizing: border-box;
        padding: 0.65rem 0.7rem;
        border-radius: 8px;
        border: 1px solid rgba(255, 255, 255, 0.3);
        background: rgba(0, 0, 0, 0.32);
        color: inherit;
      }

      button {
        width: 100%;
        margin-top: 1rem;
        padding: 0.72rem;
        border: 0;
        border-radius: 9px;
        font-weight: 700;
        cursor: pointer;
        background: #7af50f;
        color: #0f1a0a;
      }

      .status {
        margin-top: 0.8rem;
        border-radius: 8px;
        padding: 0.55rem 0.7rem;
      }

      .error {
        background: rgba(255, 94, 94, 0.18);
        border: 1px solid rgba(255, 94, 94, 0.45);
      }

      .success {
        background: rgba(122, 245, 15, 0.16);
        border: 1px solid rgba(122, 245, 15, 0.35);
      }

      .link {
        color: #9ee6ff;
      }
    </style>
  </head>
  <body>
    <main class="card">
      <h1>Portfolio Setup</h1>
      <p>Create your first admin account. This setup only works while no user exists.</p>

      <?php if (!empty($errors)): ?>
        <div class="status error">
          <ul>
            <?php foreach ($errors as $error): ?>
              <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <?php if ($success !== ''): ?>
        <div class="status success">
          <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
          <br />
          <a class="link" href="./index.html">Go to login</a>
        </div>
      <?php endif; ?>

      <?php if ($userCount === 0): ?>
        <form method="post" action="./setup.php" novalidate>
          <label for="full_name">Full name</label>
          <input id="full_name" name="full_name" type="text" value="<?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?>" required />

          <label for="email">Email</label>
          <input id="email" name="email" type="email" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" required />

          <label for="password">Password (min 8 chars)</label>
          <input id="password" name="password" type="password" required />

          <button type="submit">Create Admin Account</button>
        </form>
      <?php else: ?>
        <div class="status success">
          Setup is locked because at least one user exists.
          <br />
          <a class="link" href="./index.html">Go to login</a>
        </div>
      <?php endif; ?>
    </main>
  </body>
</html>
