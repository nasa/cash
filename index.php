<?php
    include "resources/scripts/functions.php";
    setXMLLocation();
?>
<!doctype html>
<html>
  <head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <!--CSS STYLES-->
    <style type="text/css">
      /* Here is where you can modify for colors for the main page. Each CSS class
       * refers to a different section of the page
       */

      /* Defines background for the entire page */
      .page_background { 
        /* Default background color = #213452 (blue) */
        background: url('resources/images/bg.gif') #213452 no-repeat;
      } 

      /* Defines the background color for the navbar */
      .navbar_background { 
        /* Default background color = #020036 */
        /* Default gradient colors = #020036, #07122B */
        background-color: #020036 ;
        background: linear-gradient(to right, #020036, #07122B);
      }

      /* Defines the background color for the sidebar */
      .sidebar_background { 
        /* Default background color = #020036 */
        background: #020036;
      }

      /* Defines the background color of menu items in the sidebar */
      .menu ul li { 
        /* Default background color = #4F698C */
        background:#4F698C !important;
        margin-right:5px;
        margin-bottom:1px;
      }

      /* Defines the colors for the sidebar menu item headings */
      .menu li a:not(.listhead) {
        /* Default background color = #5E8FAD */
        color:#FFFFFF;
        background:#5E8FAD;
      }

      /* Defines the background color of sidebar links
         when you hover over them */
      .menu a:hover, .menu li a:hover:not(.listhead) {
        /* Default background color = #020036 */
        background:#020036;
      }

      /* Defines the background color of sidebar links while clicking them */
      .menu a:active {
        /* Default background color = #34325e */
        background:#34325e;
      }

      /* Defines the background color of sidebar links
         once they have been clicked */
      .menu li a.linkActive {
        /* Default background color = #34325e */
        background:#34325e;
      }

      /* Defines the background color of the main content pane */
      .centerdiv_background {
        /* Default background color = #E4EEF5 */
        background: #E4EEF5;
      }

      /* Defines the colors of the footer */
      .footer_background {
        /* Default color = #FFFFFF */
        /* Default background color = #020036 */
        color: #FFFFFF; /* Text Color */
        background-color: #020036; /* Background Color */
      }

        /* Defines the text color of all hyperlinks */
    	a {
    		/* color: #0088cc; */
    		color: #0088cc;
    		text-decoration: none;
    	}

    	a:focus {
    		outline: thin dotted #333;
    		outline: 5px auto -webkit-focus-ring-color;
    		outline-offset: -2px;
    	}

    	a:hover,
    	a:active {
    		outline: 0;
    	}

        /* Defines the text color of all hyperlinks when you hover over them */
    	a:hover,
    	a:focus {
            /* Default color = #0088CC */
    		color: #0088CC
    		text-decoration: bold;
    	}
    </style>
    <!--END OF CSS STYLES-->


    <meta charset="utf-8">
    <?php
      $header = "";
      $title = "";
      $navBarContent = "";
      $navigationTitle = "";
      $navigationItems = "";
      echo getAllScriptDeclarations();
      /*
      echo generateScriptHTML('resources/css');
      echo generateScriptHTML('resources/scripts');
      echo generateScriptHTML('');
      echo generateScriptHTML('css');
       */
      //Get what we need
      $contentXML=initializeMainContent();
      if ($contentXML == null) {
          echo "<h1>Failed to load content xml file. Make sure it is properly ".
               "defined in config.xml.</h1>";
      } else {
          $title=getTitleHTML($contentXML);
          $header=getHeaderHTML($contentXML);
          //Use what we have
          echo $title;
      }
    ?>
  </head>
  
  <body>
    <div class="content page_background">
      
      <!--TOP NAVBAR-->
      <div class="top">
      <div class="top_block block_2 navbar_background">
            <!--<a href="http://www.jpl.nasa.gov/"><img src="resources/images/JPL_Logo.png"
            height="43" width="110"></a>
            <ul class="navlist">-->
            <?php
                if ($contentXML != null) {
                    $navBarContent = getNavBarHTML($contentXML);
                    echo $navBarContent;
                }
            ?>
            <!--</ul>-->
      </div>

      <!--SIDEBAR NAVIGATION-->
      <div class="sidebar_container">
        <div class="left_block block_1">
          <div class="mysidebar sidebar_background">
            <ul class="menu">      
              <?php
               //parse files. get what we need
                if ($contentXML != null) {
                	$navigationTitle = getNavigationTitle($contentXML);
                	$navigationItems = getNavigationItems($contentXML);
                	//display what we received
                	echo "<div><div id='someDivName'>$navigationTitle</div><div>$navigationItems</div></div>";
                }
              ?>
            </ul> 
          </div>
        </div>
      </div>
      <!--END OF SIDEBAR NAVIGATION-->
      
      <!--CENTER PANE-->
      <div class="centerdiv background block_5 shadow centerdiv_background">
        <div style="position: absolute; top: 0; height: 80px; left: 0; right: 0; overflow:hidden;">
          <!--HEADER FOR CENTER PANE-->
          <div style="padding-bottom:30px;">
  		      <?php echo "<center>$header</center>"; ?>
          </div>
        </div>
        <div style="position: absolute; top: 80px; left: 0px; right: 0px; bottom: 1.5em;" id="centerDiv">
          <iframe src="<?php echo $startPage; ?>"
            class="iFrame" id="myDiv" width="100%" height="100%"
            name="innerframe" frameBorder="0">Browser not compatible.
          </iframe> 
        </div>
          <div class="centerdiv_footer"></div>
        </div>
        <!--END OF CENTER PANE-->
        
        <!--FOOTER BAR-->
        <div class="footer footer_background">
          <div style="float:right">
            v1.2.8
          </div>
          <div style="text-align: center">
            <?php echo getUser(); ?>
              <?php
                //get user
                $groups = getGroups();
                $gr=""; 
                $groupsCount = count($groups);
                for ($i = 0; $i < $groupsCount; $i++) {//$groups as $group) {
                  $gr .= $groups[$i];
                  if ($i < $groupsCount-1) {
                    $gr .= ", ";
                  }
    			}
    			echo "| $gr"; 
              ?>
          </div>
        </div>
      <!--END OF FOOTER BAR-->

    </div>
  </body>
  
  <!--JAVASCRIPT-->
  
  <!--CHANGES A LINK THAT WAS CLICKED TO ACTIVE-->
  <script>
    $(".menu li a").click(function(e){
      var len = this.href.length;
      var ext = this.href.substring(len-3,len);
      remove_download_pdf(); //removes download pdf link
      if (ext == "pdf") {
        add_download_pdf(this.href);
      }
      //Changes clicked item to active
    	var link = $(this)
      linkClicked(link);
    });
  </script>

    <!--FOR WHEN A PDF IS CLICKED ON-->
    <script>
    $(".pdf").click(function(e){
      e.preventDefault();
      open_in_new_tab(this.href);
    });
  </script>

</html>
