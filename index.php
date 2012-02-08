<?php
    // Construct the path for the current URL
    $sign_in_url = "http://" . $_SERVER['SERVER_NAME'] . 
	dirname($_SERVER['PHP_SELF']) . '/sample_sign_in.php';  // fragile method, for demo purpose only
?>

<html>

<head>

<!-- 	
	The section below (until "</head>" is copied from the "show code" section on the
	SSI web site (http://ssi.sumilux.com). There are minor modifications, please note comments
  -->

<link type="text/css" rel="stylesheet" href="http://ssi.sumilux.com/ssi/download/ssi.css">
<script type="text/javascript">
window.SSI={
    tokenUrl: "<?= $sign_in_url ?>", // just prepared above
    appName:"githubsample",
    sig:"7953ed4502ea26a2a494f904cd74b0cd",
    owaUrl:"https://social-sign-in.com/smx/owa",
    v:"v0.5-1"
};
(function(){
    var e=document.createElement("script");
    e.type="text/javascript"; e.src="https://social-sign-in.com/smx/owa/js/app/HRTzgBllQJ*OY62yHcpt9Q.js";
    var f=document.createElement("script");
    f.type="text/javascript"; f.src="http://ssi.sumilux.com/ssi/download/popup.js";
    var h=document.getElementsByTagName("script")[0];
    h.parentNode.insertBefore(e, h); h.parentNode.insertBefore(f, h);
})();
</script>

</head>


<body>
<h2>SSI Sample Page</h2>


<!-- Again, code below copied from the SSI site -->
<div style="padding:20px">
    <div style="text-align:center";>
        <a href="#none" onclick="SSI.popLoginPage();">Sign In</a>
    </div>
</div>

</body>

</html>
