<link rel="preconnect" href="https://fonts.gstatic.com">
<link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">

<h1> $title Extreme Masters!</h1>
<marquee>Winner Winner 🐔 Dinner</marquee>
<div class="content">
        <div class="table-responsive">
            <table class="table tftable">
                <thead class="table-header">
                    <tr>
                        <th>Name</th>   
                        <th>Overall</th>
                        <th>Pick 1</th>
                        <th>Score</th>
                        <th>Pick 2</th>
                        <th>Score</th>
                        <th>Pick 3</th>
                        <th>Score</th>
                        <th>Pick 4</th>
                        <th>Score</th>
                    </tr>
                </thead>
                <tbody class="table-body">
                </tbody>
            </table>
        </div>
</div>

<?php

session_start();

$colors = array("red", "green", "blue", "yellow", "orange", "cyan", "purple", "pink", "blue", "orange");

if(isset($_GET['logout'])){

    $logout_message = "<div class='msgln'><span class='left-info'><b class='user-name-left' style='background: ".$_SESSION['color']."'>". $_SESSION['name'] ."</b> has left the chat session.</span><br></div>";
    file_put_contents("log.html", $logout_message, FILE_APPEND | LOCK_EX);
    session_destroy();
    header("Location: index.php");
}

$color_number = rand(1,10);
//echo $color_number;

if(isset($_POST['enter'])){
    if($_POST['name'] != ""){
        $_SESSION['name'] = stripslashes(htmlspecialchars($_POST['name']));
        $login_message = "<div class='msgln'><span class='left-info'><b class='user-name-left' style='background: limegreen'>". $_SESSION['name'] ."</b> has entered the chat session.</span><br></div>";
        file_put_contents("log.html", $login_message, FILE_APPEND | LOCK_EX);
        $color = $colors[$color_number];
        $_SESSION['color'] = $color;

    }
    else{
        echo '<span class="error">Please type in a name</span>';
    }

}


function loginForm(){
    echo
    '<div id="loginform">
    <p>Please enter your name to chat!</p>
    <form action="index.php" method="post">
      <label for="name">Name &mdash;</label>
      <input type="text" name="name" id="name" />
      <input type="submit" name="enter" id="enter" value="Enter" />
    </form>
  </div>';

}

    if(!isset($_SESSION['name'])){
        loginForm();
    }
    else {


    ?>
        <div id="wrapper">
            <div id="menu">
                <p class="welcome">Welcome, <b style="color:<?php echo $_SESSION['color'] ?>"><?php echo $_SESSION['name']; ?></b></p>
                <p class="logout"><a id="exit" href="#">Exit Chat</a></p>
            </div>

            <div id="chatbox">
            <?php
            if(file_exists("log.html") && filesize("log.html") > 0){
                $contents = file_get_contents("log.html");
                echo $contents;
            }
            ?>
            </div>

            <form name="message" action="">
                <input name="usermsg" type="text" id="usermsg" />
                <input name="submitmsg" type="submit" id="submitmsg" value="Send" />
            </form>
        </div>
        <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
        <script type="text/javascript">
            // jQuery Document
            $(document).ready(function () {
                $("#submitmsg").click(function () {
                    var clientmsg = $("#usermsg").val();
                    $.post("post.php", { text: clientmsg });
                    $("#usermsg").val("");
                    return false;
                });

                function loadLog() {
                    var oldscrollHeight = $("#chatbox")[0].scrollHeight - 5;
                    $.ajax({
                        url: "log.html",
                        cache: false,
                        success: function (html) {
                            $("#chatbox").html(html);

                            //Auto-scroll
                            var newscrollHeight = $("#chatbox")[0].scrollHeight - 5;
                            if(newscrollHeight > oldscrollHeight){
                                $("#chatbox").animate({ scrollTop: newscrollHeight }, 'normal');
                            }
                        }
                    });
                }

                setInterval (loadLog, 2500);

                $("#exit").click(function () {
                    var exit = confirm("Are you sure you want to end the session?");
                    if (exit == true) {
                    window.location = "index.php?logout=true";
                    }
                });
            });
        </script>
    </body>
</html>
<?php
}
?>