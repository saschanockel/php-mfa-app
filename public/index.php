<?php

require_once '../vendor/autoload.php';

use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Bramus\Router\Router;
use OTPHP\TOTP;
use saschanockel\PhpMfaApp\Entities\User;
use saschanockel\PhpMfaApp\Services\Database;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

// Initialize template engine
$loader = new FilesystemLoader('/var/www/html/templates');
$twig = new Environment($loader);

// Create Router instance
$router = new Router();

// Get entity manager
$em = Database::getEntityManager();

// Define authentication middleware
$router->before('GET', '/admin', function () {
    if (isset($_SESSION['username'])) {
        return true;
    } else {
        header('location: /login');
        exit();
    }
});

// Define routes
$router->get('/login', function () use ($twig) {
    if (isset($_SESSION['username'])) {
        header('location: /admin');
    } else {
        echo $twig->render('login.html.twig');
    }
});
$router->post('/login', function () use ($em) {
    if (isset($_POST['username']) && isset($_POST['password'])) {
        session_destroy();

        $query = $em->createQuery('SELECT u FROM \saschanockel\PhpMfaApp\Entities\User u WHERE u.username = ?1');
        $query->setParameter(1, $_POST['username']);
        /* @var User $user */
        $user = $query->getSingleResult();

        if ($user->getOtpSecret()) {
            $otp = TOTP::create($user->getOtpSecret());
            // If the user has a configured OTP secret verify it together with the password
            if (password_verify($_POST['password'], $user->getPassword()) && $otp->verify($_POST['oneTimePassword'])) {
                session_start();
                $_SESSION['username'] = $_POST['username'];
                header('location: /admin');
            }
        } elseif (password_verify($_POST['password'], $user->getPassword())) {
            session_start();
            $_SESSION['username'] = $_POST['username'];
            header('location: /admin');
        }
    }

    header('location: /login');
    exit();
});
$router->get('/signup', function () use ($twig) {
    echo $twig->render('signup.html.twig');
});
$router->post('/signup', function () use ($em) {
    if (isset($_POST['username']) &&
        isset($_POST['password']) &&
        isset($_POST['confirmPassword']) &&
        ($_POST['password'] === $_POST['confirmPassword'])) {
        $user = new User();

        $user->setUsername($_POST['username']);
        $user->setPassword(password_hash($_POST['password'], PASSWORD_DEFAULT));

        $em->persist($user);
        $em->flush($user);
    } else {
        header('location: /signup');
        exit();
    }
});
$router->post('/logout', function () use ($em) {
    session_destroy();
    header('location: /login');
});

$router->get('/admin', function () use ($twig) {
    // A random secret will be generated from this.
    // You should store the secret with the user for verification.
    $otp = TOTP::create();
    $otp->setLabel('PHP MFA App');
    $renderer = new ImageRenderer(
        new RendererStyle(400),
        new ImagickImageBackEnd()
    );
    $writer = new Writer($renderer);
    // save the QR code, so it can be displayed on the website
    $writer->writeFile($otp->getProvisioningUri(), '/var/www/html/public/img/qrcode.png');
    /**
     * The OTP secret is stored in plain text since the server needs to know it to calculate the TOTP Token to compare with the user provided one.
     * Also the OTP secret represents something you "own", in combination with something you "know" (the hashed password which is not readable by the server)
     * this procedure is safe and provides additional security against account theft and brute force attacks.
     *
     * We store the secret in the session so we can verify it if the user registers an OTP without calling the DB
     */
    $_SESSION['otpSecret'] = $otp->getSecret();
    echo $twig->render('admin.html.twig');
});
$router->post('/admin', function () use ($twig, $em) {
    $otp = TOTP::create($_SESSION['otpSecret']);
    if ($otp->verify($_POST['oneTimePassword'])) {
        // If the generated passcode is ok we can save the secret permanently
        $query = $em->createQuery('SELECT u FROM \saschanockel\PhpMfaApp\Entities\User u WHERE u.username = ?1');
        $query->setParameter(1, $_SESSION['username']);
        /* @var User $user */
        $user = $query->getSingleResult();

        $user->setOtpSecret($_SESSION['otpSecret']);
        $em->persist($user);
        $em->flush($user);
    }

    echo $twig->render('admin.html.twig');
});

// Run it!
$router->run();
