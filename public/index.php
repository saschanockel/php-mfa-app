<?php

require_once '../vendor/autoload.php';

use Bramus\Router\Router;
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

        if (password_verify($_POST['password'], $user->getPassword())) {
            session_start();
            $_SESSION['username'] = $_POST['username'];
            header('location: /admin');
        }
    } else {
        header('location: /login');
        exit();
    }
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
    echo $twig->render('admin.html.twig');
});

// Run it!
$router->run();
