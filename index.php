<?php
    session_start();
    if ((!isset($_SESSION['logged'])) || ($_SESSION['logged'] != true))
	{
		header('Location: login.php');
		exit();
	}

     //TODO: Add option to choose default mailbox.
     if ($_SERVER['QUERY_STRING'] == "") {
        header("Location: ?inbox");
        exit();
    }

    if (isset($_GET['logout'])) {
        unset($_SESSION['logged']);
        unset($_SESSION['id']);
        unset($_SESSION['user']);
        header('Location: login.php');
        exit();
    }
    $actionTaken = false;
    require_once("connection.php");
    $connection = new mysqli($hostname, $username, $dbpassword, $database);
    if (isset($_GET['remove'])) {
        $mailID = $_GET['remove'];
        $connection->query("UPDATE bmail_mails SET archived = 1 WHERE ID='$mailID'");
        $actionTaken = true;
        unset($_GET['remove']);
    }
    else if (isset($_GET['undo-remove'])) {
        $mailID = $_GET['undo-remove'];
        $connection->query("UPDATE bmail_mails SET archived = 0 WHERE ID='$mailID'");
        $actionTaken = true;
        unset($_GET['undo-remove']);
        header("Location: index.php");
    }
    else if (isset($GET['spam'])) {
        $actionTaken = true;
        unset($GET['spam']);
        header("Location: index.php");
        //Why do I need this? It would mean that I have much more to do while this is not crucial.
    }

    if (isset($_POST['new-message'])) {
        unset($_POST['new-message']);
        $correct = true;

        $receiverEmail = str_replace("@bmail.com", "", $_POST['receiver']);
        $subject = $_POST['subject'];
        $content = $_POST['content'];
        
        $receiverEmail = htmlentities($receiverEmail, ENT_QUOTES, "UTF-8");

        $subject = htmlentities($subject, ENT_QUOTES, "UTF-8");
        $content = htmlentities($content, ENT_QUOTES, "UTF-8");

        $results = $connection->query("SELECT ID FROM bmail_users WHERE user = '$receiverEmail'");
        if (!$results) {
            exit();
        }
        if ($results->num_rows <= 0) {
            $_SESSION['e-receiver'] = 'Wybrany użytkownik nie istnieje! Sprawdź poprawność danych.';
        }
        else {
            $row = $results->fetch_assoc();
            $receiverID = (int) $row['ID'];
            $senderID = (int) $_SESSION['id'];
            $currentTimestamp = time();
    
            $results->free_result();
            $connection->query("INSERT INTO bmail_mails VALUES (null, $receiverID, $senderID, 0, 0, '$subject', '$content', $currentTimestamp, 0)");
            $_SESSION['valid-infobox'] = "Pomyślnie wysłano wiadomość!";
        }
    }
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Skrzynka odbiorcza | Bmail</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://kit.fontawesome.com/5834cec5b8.js" crossorigin="anonymous"></script>
</head>
<body>
    <!-- <form method="post">
        <input type="submit" name="logout">
    </form> -->
    <div id="navbar">
        <div class="navbar-item navbar-inbox-left">
            <a href="index.php" class="logo"><img src="img/logo.svg" alt="Logo"></a>
        </div>
        <div class="navbar-item navbar-inbox-center"><a href="https://github.com/BinaRRR">BINAR</a></div>
        <div class="navbar-item navbar-inbox-right">
            <p>Wersja 1.0</p>
            <p>Stabilna</p>
        </div>
    </div>
    <div class="main-inbox">
        <div class="sidebar">
            <div class="sidebar-account">
                <p>Witaj,</p>
                <img src="img/avatar.png">
                <p><?php echo $_SESSION['user']; ?></p>
            </div>
            <div class="sidebar-categories">
                <a href="?new" <?php
                if (isset($_GET['new']))
                    echo "class='active'";
            ?>>
                    <p class="fa-solid fa-pen-to-square"></p>
                    <p>Napisz wiadomość</p>
                </a>
                <a href="?inbox" <?php
                if (isset($_GET['inbox']))
                    echo "class='active'";
            ?>>
                    <p class="fa-solid fa-envelope"></p>
                    <p>Skrzynka odbiorcza</p>
                </a>
                <a href="?sent" <?php
                if (isset($_GET['sent']))
                    echo "class='active'";
            ?>>
                    <p class="fa-solid fa-paper-plane"></p>
                    <p>Skrzynka nadawcza</p>
                </a>
                <a href="?archive" <?php
                if (isset($_GET['archive']))
                    echo "class='active'";
            ?>>
                    <p class="fa-solid fa-box-archive"></p>
                    <p>Kosz / Archiwum</p>
                </a>
            </div>
            <div class="sidebar-options">
                <a href="?settings" <?php
                if (isset($_GET['settings']))
                    echo "class='active'";
                ?>>
                    <p class="fa-solid fa-gear"></p>
                    <p>Ustawienia</p>
                </a>
                <a href="?logout">
                    <p class="fa-solid fa-right-from-bracket"></p>
                    <p>Wyloguj się</p>
                </a>
            </div>
        </div>
        <div class="split-line"></div>
        <div class="main-inbox-mails">
        <?php
            if (isset($_GET['settings'])) {
        ?>
            <h1 class="h1-new-mail-title">Ustawienia skrzynki pocztowej</h1>
            <p style="text-align: center;">Brak tu treści. To tylko miejsce, w którym możnaby ustawić swoje ustawienia.</p>
        <?php
           }
        ?>


        <?php
            if (isset($_GET['new']) || isset($_GET['reply'])) {
                $reply;
                isset($_GET['reply']) ? $reply = true: $reply = false;
                if ($reply) {
                    $mailID = $_GET['reply'];
                    $results = $connection->query("SELECT u.user, m.title, m.content FROM bmail_mails AS m, bmail_users AS u WHERE m.ID = '$mailID' AND u.ID = m.senderID");
                    if (!$results) {
                        exit();
                    }
                    $row = $results->fetch_array();
                    $results->free_result();
                }
        ?>
        <h1 class="h1-new-mail-title"> <?php printf("%s", $reply ? 'Odpowiedź na wiadomość mailową' : 'Nowa wiadomość mailowa') ?> </h1>
        <form method="post">
            <div class="new-mail-inputs">
                <label for="receiver">Odbiorca: </label>
                <input type="text" name="receiver" id="receiver" placeholder="twojznajomy@bmail.com" <?php if($reply) echo 'value="'.$row[0].'@bmail.com" readonly ' ?> required>
                <?php
                    if (isset($_SESSION['e-receiver'])) {
                        echo "<span class='form-error'>".$_SESSION['e-receiver']."</span>";
                        unset($_SESSION['e-receiver']);
                    }
                ?>
                <label for="subject">Temat: </label>
                <input type="text" name="subject" id="subject" placeholder="Historia mrocznego lasu" <?php if($reply) echo 'value="Re: '.$row[1].'" readonly ' ?> required>
            </div>
            <div class="new-mail-textarea">
                <label for="content">Treść wiadomości: </label>
                <textarea name="content" placeholder="W ciemnym lesie spowitym mgłą..." rows="10"><?php if($reply) echo 'Poprzednia wiadomość: &quot;'.$row[1].'&quot;' ?></textarea>
            </div>
            <div class="new-mail-submit">
                <input type="submit" name="new-message" value="Wyślij">
            </div>
        <?php
            exit(); }
            if (isset($_GET['id'])) {
                $mailID = $_GET['id'];

                $results_isReceiver = $connection->query("SELECT m.ID FROM bmail_mails AS m, bmail_users AS u WHERE m.id = $mailID AND m.receiverID = $userID");
                if ($results_isReceiver->num_rows >= 1)
                    echo "<h1 class='h1-new-mail-title'>Wiadomość mailowa od</h1>";
                else
                    echo "<h1 class='h1-new-mail-title'>Wiadomość mailowa do</h1>";
                $results_isReceiver->free_result();

                echo "<div class='mail-inside'>";
                echo "<h1 class='h1-new-mail-title'>Wiadomość mailowa od</h1>";
                echo "<h3 class='h3-mail-sender'></h3>";
                $results = $connection->query("SELECT u.user, m.title, m.date, m.content FROM bmail_users AS u, bmail_mails AS m WHERE m.id = '$mailID' AND m.senderID = u.ID");
                if (!$results) {
                    exit();
                }

                $row = $results->fetch_array();

                $timestamp = (int) $row[2];
                setlocale(LC_ALL, 'pl-PL');
                $time = strftime('%d %b %R', $timestamp);
                $results->free_result();
                echo "<div class='mail-user-section'>";
                echo "<p>".$row[0]."@bmail.com</p>";
                echo "</div>";

                echo "<div class='mail-rest-section'>";
                echo "<div class='mail-title'>";
                echo "<p>".$row[1]."</p>";
                echo "</div>";

                echo "<div class='mail-date'>";
                echo "<p>".$time."</p>";
                echo "</div>";
                echo "</div>";
                echo "<div class='mail-content'>";
                echo "<p>".$row[3]."</p>";
                echo "</div>";
                echo "</div>";

                $userID = $_SESSION['id'];
                $results_isReceiver = $connection->query("SELECT m.ID FROM bmail_mails AS m, bmail_users AS u WHERE m.id = $mailID AND m.receiverID = $userID");
                if ($results_isReceiver->num_rows >= 1) {
                    $connection->query("UPDATE bmail_mails SET mailRead = 1 WHERE ID = $mailID");
        ?>
        <div class="mail-action-buttons">
            <form method="get" action="index.php">
            <?php
            $id = $_GET['id'];
                echo "<button class='ab action-button-reply' type='submit' name='reply' value='$id'>Odpowiedz</button>";
                    $results = $connection->query("SELECT archived FROM bmail_mails WHERE ID='$mailID'");
                    if (!$results) {
                        exit();
                    }

                    $row = $results->fetch_assoc();
                    $results->free_result();
                    if (!boolval($row['archived']))
                        echo "<button class='ab action-button-reply'  type='submit' name='remove' value='$id'>Przenieś do kosza</button>";
                    else
                        echo "<button class='ab action-button-reply' type='submit' name='undo-remove' value='$id'>Przywróć do skrzynki</button>";
                    echo "<button class='ab action-button-spam' type='submit' name='reply' value='$id'>Oznacz jako spam</button>";  
                ?>
                
                </form>
            <?php
                } exit(); }
            ?>
            <table>
                <?php
                    $id = $_SESSION['id'];
                    switch($_SERVER['QUERY_STRING']) {
                        case 'inbox':
                            $results = $connection->query("SELECT u.user, m.title, m.date, m.ID, m.mailRead FROM bmail_users AS u, bmail_mails AS m WHERE m.archived = 0 AND m.receiverID = '$id' AND u.ID = m.senderID ORDER BY m.date DESC");
                            echo '<tr><th>Nadawca</th><th>Tytuł</th><th>Data</th></tr>';
                            break;
                        case 'sent':
                            $results = $connection->query("SELECT u.user, m.title, m.date, m.ID FROM bmail_users AS u, bmail_mails AS m WHERE m.archived = 0 AND m.senderID = '$id' AND u.ID = m.receiverID ORDER BY m.date DESC");
                            echo '<tr><th>Odbiorca</th><th>Tytuł</th><th>Data</th></tr>';
                            break;
                        case 'archive':
                            $results = $connection->query("SELECT u.user, m.title, m.date, m.ID FROM bmail_users AS u, bmail_mails AS m WHERE m.archived = 1 AND m.receiverID = '$id' AND u.ID = m.senderID ORDER BY m.date DESC");
                            echo '<tr><th>Nadawca</th><th>Tytuł</th><th>Data</th></tr>';
                            break;
                        default:
                            exit();
                            break;
                    }
                        
                    if (!$results) {
                        exit();
                    }
                    if ($results->num_rows < 1) {
                        exit();
                    }
                    while ($row = $results->fetch_array()) {
                        $timestamp = (int) $row[2];
                        setlocale(LC_ALL, 'pl-PL');
                        $time = strftime('%d %b %R', $timestamp);
                        switch($_SERVER['QUERY_STRING']) {
                            case 'inbox':
                                $mailRead = (bool) $row['mailRead'];
                                if (!$mailRead)
                                    echo "<tr class='mail-read'>";
                                else
                                    echo "<tr>";
                                break;
                            default:
                                echo "<tr>";
                                break;
                        }
                        echo "<td><a href='?id=".$row[3]."'>".$row[0]."</a>"."</td>";
                        echo "<td><a href='?id=".$row[3]."'>".$row[1]."</a>"."</td>";
                        echo "<td><a href='?id=".$row[3]."'>".$time."</a>"."</td>";
                        echo "</tr>";
                    }
                    $results->free_result();
                    $connection->close();
                ?>
                <?php

                ?>
            </table>
        </div>
    </div>
</body>
</html>