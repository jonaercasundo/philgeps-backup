<?php
session_start();
$num1 = rand(1,9);
$num2 = rand(1,9);
$_SESSION['captcha'] = $num1 + $num2;

// Return the string instead of echoing
return "What is $num1 + $num2 ?";
