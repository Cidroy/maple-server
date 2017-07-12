    <footer class="page-footer grey darken-4">
          <div class="container">
            <div class="row">
              <?php if(MAPLE::is_loggedin()):?>
              <div class="col l4 s12 m6 hide-on-med-and-down">
                <h5 class="white-text">User</h5>
                <ul id='user_settings'>
                  <li><a class="grey-text text-lighten-4" href='<?php echo URL::name('user','profile')?>' class="fg-white2 fg-hover-yellow">Profile</a></li>
                  <li><a class="grey-text text-lighten-4 maple-logout" href='<?php echo LOGIN::LogoutUrl()?>' class="fg-white3 fg-hover-yellow">Logout</a></li>
                </ul>
              </div>
              <?php endif;?>
            </div>
          </div>
          <div class="footer-copyright black">
            <div class="container">
            &copy; 2017 <?php echo SITE::Name()?>, Copyrights Reserved
            <a class="grey-text text-lighten-4 right" href="http://rubixcode.com">developed by Team Rubixcode</a>
            </div>
          </div>
        </footer>
    <?php foreach (TEMPLATE::CSS() as $css):?>
    <link rel="stylesheet" href="<?php echo $css?>" >
  <?php endforeach; ?>
    <script src="<?php echo URL::http("%THEME%jquery.js")?>" ></script>
    <script src="<?php echo URL::http("%THEME%Materialize/js/materialize.min.js")?>" ></script>
    <?php foreach (TEMPLATE::JS() as $script):?>
      <script src="<?php echo $script?>" ></script>
    <?php endforeach;?>
    <?php echo TEMPLATE::Footer(); ?>
    <script type="text/javascript">
      $(document).ready(function(){
        $('.carousel').carousel();
        setInterval(function(){$('.carousel').carousel('next');},3000);
        $(".button-collapse").sideNav();
        $(".dropdown-button").dropdown();
        $("ul.tabs").tabs();
		$('input').characterCounter();

//        $("table").dataTable({'searching':true});
        $('.datepicker').pickadate({
          selectMonths: true, // Creates a dropdown to control month
          selectYears: 1 // Creates a dropdown of 15 years to control year
        });
      });
      document.title="<?php echo TEMPLATE::Title();?>";
      $('.maple-logout').on('click',function(e){
        e.preventDefault();
        if(confirm("Logout?")){
          var url = $(this).attr('href');
          window.location.href = url;
        }
      });
    </script>
    <?php
	// filter body_end_content
	echo MAPLE::do_filters('body_end_content');
	?>
  </div><!--body wrapper-->
  <?php
  echo ERROR::ShowDebugBar();
  ?>
</body>

</html>
