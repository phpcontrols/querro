<nav id="navbar" class="navbar navbar-inverse navbar-fixed-top">
  <div class="container-fluid">
    <!-- Brand and toggle get grouped for better mobile display -->
    <div class="navbar-header">
      <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
      <a class="navbar-brand" href="/index.php" class='active'><img src="/resources/images/logo.png" width="20" title="home" alt="home"></a>
    </div>

    <!-- Collect the nav links, forms, and other content for toggling -->
    <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
        <ul class="nav navbar-nav">
            <li<?php if (strpos($current_page, 'query') === 0) echo ' class="active"'; ?>><a href="/q/query.php">Query</a></li>
            <li<?php if (strpos($current_page, 'settings') === 0) echo ' class="active"'; ?>><a href="/q/settings.php">Settings</a></li>
        </ul>
        <ul class="nav navbar-nav navbar-right">
          <li style="float:right;display:block"><a href="/logout">Logout</a></li>
        </ul>
        <p class="navbar-text navbar-right">
        <a href="/q/help.php"><i class="fa fa-question-circle" style="font-size:16px"></i></a>&nbsp;&nbsp;
          Welcome <?= ucfirst($_SESSION['Username']) ?>!&nbsp;&nbsp;&nbsp;| 
        </p>
    </div><!-- /.navbar-collapse -->
  </div><!-- /.container-fluid -->
</nav>