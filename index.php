<?php
/**
 * Created by PhpStorm.
 * User: rochb
 * Date: 27/01/2017
 * Time: 21:35
 */

session_start();

// INCLUDES
$app = include_once (__DIR__ . '/config/app.php');
$db = include_once (__DIR__ . '/config/database.php');
include_once (__DIR__ . '/includes/functions.php');

// SESSIONS
if(empty($_SESSION['start_date'])){
    $_SESSION['start_date'] = date('h:i d/m/y');
    writeLog($app, 'New session start on : ' . $_SERVER['REMOTE_ADDR']);
}

// DATABASE
$DB = new PDO('mysql:host='.$db['host'].';dbname='.$db['name'].';charset=utf8', $db['user'], $db['password']);

// RESPONSES
if(empty($_GET['action'])){
    include_once (__DIR__ . '/includes/views/404.php');
    http_response_code(404);
}else{
    if($_GET['action'] == 'sendMail'){
        if(empty($_GET['title']) && empty($_GET['body']) && empty($_GET['from']) && empty($_GET['to']) && empty($_GET['token'])){
            include_once (__DIR__ . '/includes/views/404.php');
            http_response_code(404);
        }else{
            $query = $DB->prepare('SELECT * FROM tokens WHERE token = "'.$_GET['token'].'"');
            $query->execute();

            if($query->rowCount() >= 1){
                $data = $query->fetch();
                if($data['actions'] == 'sendMail'){
                    $query = $DB->prepare('INSERT INTO mails (title, from_email, to_email, body) VALUES ("'.$_GET['title'].'", "'.$_GET['from'].'", "'.$_GET['to'].'", "'.$_GET['body'].'")');
                    $query->execute();

                    $query = $DB->prepare('SELECT * FROM mails ORDER BY created_at DESC');
                    $query->execute();
                    $data = $query->fetch();

                    $token = bin2hex(random_bytes(120));

                    $query = $DB->prepare('INSERT INTO tokens (token, actions, parameters) VALUES ("'.$token.'", "getMail", :id)');
                    $query->bindValue(':id', $data['ID']);
                    $query->execute();


                    $destinataire = $_GET['to'];
                    $expediteur = $_GET['from'];
                    $objet = $_GET['title'];
                    $headers  = 'MIME-Version: 1.0' . "\n";
                    $headers .= 'Content-type: text/html; charset=ISO-8859-1'."\n";
                    $headers .= 'Reply-To: '.$expediteur."\n";
                    $headers .= 'From: <'.$expediteur.'>'."\n";
                    $headers .= 'Delivered-to: '.$destinataire."\n";
                    $message = $_GET['body'];
                    if (mail($destinataire, $objet, $message, $headers)) {
                        http_response_code(200);
                    } else {
                        include_once (__DIR__ . '/includes/views/404.php');
                        http_response_code(404);
                    }
                }else{
                    include_once (__DIR__ . '/includes/views/REFUSED.php');
                    http_response_code(403);
                }
            }else{
                include_once (__DIR__ . '/includes/views/WRONG_TOKEN.php');
                http_response_code(401);
            }
        }
    }else if($_GET['action'] == 'getMail'){
        if(empty($_GET['id']) && empty($_GET['token'])){
            include_once (__DIR__ . '/includes/views/404.php');
            http_response_code(404);
        }else{
            $query = $DB->prepare('SELECT * FROM tokens WHERE token = "'.$_GET['token'].'"');
            $query->execute();
            if($query->rowCount() >= 1) {
                $data = $query->fetch();
                if($data['actions'] == 'getMail'){
                    if($data['parameters'] == $_GET['id']){
                        $query = $DB->prepare('SELECT * FROM mails WHERE id = "'.$_GET['id'].'"');
                        $query->execute();
                        $data = $query->fetch();
                        echo '<title>' . $app['name'] . ' | ' . $data['title'] .'</title>';
                        echo $data['body'];
                        http_response_code(200);
                    }else{
                        include_once (__DIR__ . '/includes/views/REFUSED.php');
                        http_response_code(403);
                    }
                }else{
                    include_once (__DIR__ . '/includes/views/REFUSED.php');
                    http_response_code(403);
                }
            }else{
                include_once (__DIR__ . '/includes/views/WRONG_TOKEN.php');
                http_response_code(403);
            }

        }
    }else if($_GET['action'] == 'getAllMails'){
        if(empty($_GET['token'])){
            include_once (__DIR__ . '/includes/views/404.php');
            http_response_code(404);
        }else{
            $query = $DB->prepare('SELECT * FROM tokens WHERE token = "'.$_GET['token'].'"');
            $query->execute();
            if($query->rowCount() >= 1) {
                $data = $query->fetch();
                if($data['actions'] == 'getAllMails'){
                    $query = $DB->prepare('SELECT * FROM mails ORDER BY created_at desc');
                    $query->execute();
                    $mails = [];

                    while($data = $query->fetch()){
                        array_push($mails, [
                            'title' => $data['title'],
                            'from' => $data['from_email'],
                            'to' => $data['to_email'],
                            'created_at' => $data['created_at'],
                        ]);
                    }

                    echo json_encode($mails);
                    http_response_code(200);
                }else{
                    include_once (__DIR__ . '/includes/views/REFUSED.php');
                    http_response_code(403);
                }
            }
        }
    }
}

/**
 *
 *  => Action
 * => sendMail ($title, $body, $from, $to, $token) !200!,
 * => getMail ($id, $token) [JSON] !200!,
 * => getAllMails ($token) [JSON] !200!,
 * => deleteMail ($token) [JSON] !200!,
 *
 */