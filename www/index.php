<!DOCTYPE html>
<html lang="en">

  <head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <style>
      .img-fix {
            display: block;
            margin-left: auto;
            margin-right: auto;
            max-width: 75% !important;
      }

    </style>

    <title>PayIOTA - IOTA Payment Gateway</title>
    <?php include('header.php'); ?>

    <meta property="og:image" content="https://payiota.me/resources/payiota_icon.png"/>

  </head>

  <body>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top">
      <div class="container">
        <a class="navbar-brand" href="/"><img src="resources/payiota_logo3.png" height="40" alt=""></a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarResponsive">
          <ul class="navbar-nav ml-auto">
            <li class="nav-item">
              <a class="nav-link" href="mailto:support@payiota.me">Support</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="https://github.com/lacicloud/payiota/wiki/PayIOTA-API-Documentation">Documents</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="/account.php?register">Sign-up</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="/account.php">Log-in</a>
            </li>
          </ul>
        </div>
      </div>
    </nav>

    <!-- Header -->
    <header class="intro-header">
      <div class="container">
        <div class="intro-message">
		
		<?php
		$header_copy = array(
		"<h2>PayIOTA</h2>
		<h3>Your Payment Gateway For The Fee-less IOTA CryptoCurrency</h3>",
		"<h2>Send Direct Payments</h2> <h3>Around The World With <strong>Zero Fees</strong></h3>",
		"<h2>Send Payments With The Power Of IOTA</h2>",
		"<h2>Payments For The Modern Web</h2><h3>Send payments securely to anyone without fees</h3>");
		echo $header_copy[array_rand($header_copy, 1)];
		?>		
         
          <hr class="intro-divider">
          <ul class="list-inline intro-social-buttons black_overlay_box">
            <li class="list-inline-item">
              <a href="https://github.com/lacicloud/payiota/wiki/PayIOTA-API-Documentation" class="btn btn-secondary btn-lg">
                <i class="fa fa-file fa-fw"></i>
                <span class="network-name">Docs</span>
              </a>
            </li>
            <li class="list-inline-item">
              <a href="https://github.com/lacicloud/payiota" class="btn btn-secondary btn-lg">
                <i class="fa fa-github fa-fw"></i>
                <span class="network-name">Github</span>
              </a>
            </li>
            <li class="list-inline-item">
              <a href="https://github.com/lacicloud/payiota-woocommerce/" class="btn btn-secondary btn-lg">
                <i class="fa fa-wordpress fa-fw"></i>
                <span class="network-name">WooCommerce</span>
              </a>
            </li>
            <li class="list-inline-item">
              <a href="/account.php" class="btn btn-secondary btn-lg">
                <i class="fa fa-sign-in fa-fw"></i>
                <span class="network-name">Account</span>
              </a>
            </li>
          </ul>
        </div>
      </div>
    </header>

    <!-- Page Content -->
    <section class="content-section-a">

      <div class="container">
		<div class="row top_features">
			<div class="col-md-4">
			<i class="fa fa-exchange" aria-hidden="true"></i>
            <h3 class="section-heading">Fast Payments</h3>
			<p>The more transactions the IOTA network handles, the faster payments are completed</p>
			</div>			
			<div class="col-md-4">
			<i class="fa fa-lock" aria-hidden="true"></i>
            <h3 class="section-heading">Safe & Secure</h3>
			<p>IOTA is virtually immune to the effects of quantum computation based attacks</p>
			</div>
			<div class="col-md-4">
			<i class="fa fa-globe" aria-hidden="true"></i>
            <h3 class="section-heading">Zero Fees</h3>
			<p>There are no charges for anything that you send, enabling the use of micropayments</p>
			</div>
		</div>
      </div>
      <!-- /.container -->
    </section>
	  	  
	<section class="content-section-b photo_features right_image">
		<div class="left-block">
			<hr class="section-heading-spacer">
			<div class="clearfix"></div>
			<h2 class="section-heading">Your Secure Payment Gateway</h2>
			<p class="lead">PayIOTA helps web merchants accept IOTA payments easily on their websites with no fees. 
			The <a href="https://github.com/lacicloud/payiota/wiki/PayIOTA-API-Documentation">PayIOTA API</a> helps you accept payments for products and services on your own website(s). 
			<br><br>PayIOTA is a collaboration of IOTA supporters from around the world, who want to help you unlock the power of fee-less payments.
			PayIOTA is available as a <a href="https://github.com/lacicloud/payiota-woocommerce/">Woocommerce Plugin</a> for Wordpress, with more platforms to be supported as demand permits.</p>
		</div>
		<img class="img-fluid img-fix" src="resources/hero-6.jpg" alt="">
      <!-- /.container -->
    </section>
    <!-- /.content-section-b -->
	  
	  
	<section class="content-section-a photo_features left_image">
		<div class="right-block">
			<hr class="section-heading-spacer">
			<div class="clearfix"></div>
			<h2 class="section-heading">Going Beyond Blockchain</h2>
            <p class="lead">IOTA is a ground breaking open-source distributed ledger. The system was not built using blockchain technology, but a new directed acyclic graph protocol called The Tangle. <br><br>
			
			Primarily built for the purpose of machine-to-machine transactions (Internet of Things), IOTA is also proving useful for peer to peer micro-transactions and remittances.
			
			<br><br><strong>The Power of Valueless Transactions</strong><br>
			IOTA is able to handle 0 IOTA transactions, which lends to sending data from one machine to another. 
			This will enable the use of fee-less secure messaging, and a host of other services that will help to speed up the overall network.</p>
         </div>
		<img class="img-fluid img-fix" src="resources/feature-2.jpg" alt="">
      <!-- /.container -->
    </section>
    <!-- /.content-section-a -->
	
	  
	<section class="content-section-b photo_features right_image">
		<div class="left-block">
			<hr class="section-heading-spacer">
			<div class="clearfix"></div>
			  <h2 class="section-heading">Unlocking Micropayments</h2>
            <p class="lead">The IOTA Tangle allows for fee-less micropayments which is opening up an entirely new sector of the market. Products and services that would be difficult to charge for in the past can now be handle electronically.
			<br><br> IOTA is able to operate without scaling issues that most traditional cryptocurrencies suffer from. 
			Micropayments allows businesses and storeowners to sell/rent content, information, products and services by the second, instead of by larger blocks of time.<br><br>
			</p>
          </div>
		<img class="img-fluid img-fix" src="resources/hero-8.jpg" alt="">
      <!-- /.container -->
    </section>
    <!-- /.content-section-b -->

    <section class="content-section-a ending_section">
      <div class="container faq">
		<div class="row">
		  <div class="col-xs-6 col-sm-4"><a href="https://iota.org/IOTA_Whitepaper.pdf" title="IOTA Whitepaper">Read The Whitepaper <i class="fa fa-angle-right" aria-hidden="true"></i></a></div>
		  <div class="col-xs-6 col-sm-4"><a href="https://github.com/lacicloud/payiota/wiki/PayIOTA-API-Documentation" title="PayIOTA Documentation">View Our Documentation <i class="fa fa-angle-right" aria-hidden="true"></i></a></div>
		  <div class="col-xs-6 col-sm-4"><a href="mailto:support@payiota.me" title="Get PayIOTA Support">Get Support<i class="fa fa-angle-right" aria-hidden="true"></i></a></div>
		</div>
      </div>
      <!-- /.container -->
    </section>
    <!-- /.content-section-a -->

    <aside class="banner">

      <div class="container">

        <div class="row">
          <div class="col-lg-6 my-auto">
            <h2>Get Started:</h2>
          </div>
          <div class="col-lg-6 my-auto">
            <ul class="list-inline banner-social-buttons">
              <li class="list-inline-item">
                <a href="/account.php" class="btn btn-secondary btn-lg">
                  <i class="fa fa-sign-in fa-fw"></i>
                  <span class="network-name">Register Account</span>
                </a>
              </li>
            </ul>
          </div>
        </div>

      </div>
      <!-- /.container -->

    </aside>
    <!-- /.banner -->

    <!-- Footer -->
    <footer>
      <div class="container">
        <ul class="list-inline">
          <li class="list-inline-item">
            <a href="/">Home</a>
          </li>
          <li class="footer-menu-divider list-inline-item">&sdot;</li>
          <li class="list-inline-item">
            <a href="mailto:support@payiota.me">Support</a>
          </li>
          <li class="footer-menu-divider list-inline-item">&sdot;</li>
          <li class="list-inline-item">
            <a href="https://github.com/lacicloud/payiota/wiki/PayIOTA-API-Documentation">Documents</a>
          </li>
          <li class="footer-menu-divider list-inline-item">&sdot;</li>
          <li class="list-inline-item">
            <a href="/account.php">Account</a>
          </li>
          <li class="footer-menu-divider list-inline-item">&sdot;</li>
          <li class="list-inline-item">
            <a href="/resources/payiota_legal.pdf">Legal</a>
          </li>
            <li class="footer-menu-divider list-inline-item">&sdot;</li>
          <li class="list-inline-item">
            <a href="/humans.txt">Owners</a>
          </li>
        </ul>
        <p class="copyright text-muted small">Copyright &copy; PayIOTA 2017. All Rights Reserved</p>
      </div>
    </footer>

  </body>

</html>
