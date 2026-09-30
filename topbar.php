<div class="topbar">
   <nav class="navbar navbar-expand-lg navbar-light">
      <div class="full" style="width: 100%;"> <button type="button" id="sidebarCollapse" class="sidebar_toggle"><i class="fa fa-bars"></i></button>
         
         <div class="logo_section" style="display: inline-block; float: left; margin-left: 15px; padding-top: 10px;">
            <a href="dashboard.php" style="text-decoration: none;">
                <img src="images/logo_drs_w.png" alt="Logo" 
                     style="width: 100px; height: 30px; vertical-align: middle;">
            </a>
         </div>

         <div class="right_topbar" style="float: right;"> <div class="icon_info">
                 <ul class="user_profile_dd" style="list-style: none; margin: 0; padding: 0;">
                     <li class="dropdown"> <a class="dropdown-toggle" href="#" role="button" data-toggle="dropdown" aria-expanded="false" style="text-decoration: none;">
                             <i class="fa fa-user" style="color: white;"></i>
                             <span class="name_user" style="color: white; font-weight: 500;">
                                 <?php echo escape_html($_SESSION['nama_penuh'] ?? 'Admin'); ?>
                             </span>
                         </a>
                         
                         <div class="dropdown-menu dropdown-menu-right user-menu-dropdown">
                             <div class="user-menu-summary">
                                 <span class="user-menu-avatar" aria-hidden="true">
                                     <i class="fa fa-user"></i>
                                 </span>
                                 <span class="user-menu-identity">
                                     <strong><?php echo escape_html($_SESSION['nama_penuh'] ?? 'Admin'); ?></strong>
                                     <small>Akaun pentadbir</small>
                                 </span>
                             </div>
                             <div class="dropdown-divider"></div>
                             <a class="dropdown-item user-menu-item" href="profil_saya.php">
                                 <i class="fa fa-user-circle-o" aria-hidden="true"></i>
                                 <span>Profil Saya</span>
                             </a>
                             <form method="post" action="logout.php" class="m-0">
                                 <input type="hidden" name="csrf_token" value="<?php echo escape_html(csrf_token()); ?>">
                                 <button type="submit" class="dropdown-item user-menu-item user-menu-logout">
                                     <i class="fa fa-sign-out" aria-hidden="true"></i>
                                     <span>Log Keluar</span>
                                 </button>
                             </form>
                         </div>
                     </li>
                 </ul>
             </div>
         </div>
         
         <div style="clear: both;"></div>

      </div>
   </nav>
</div>
