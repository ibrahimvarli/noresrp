        </main>
        
        <footer class="main-footer">
            <div class="footer-content">
                <div class="footer-logo">
                    <img src="<?php echo SITE_URL; ?>assets/images/logo.png" alt="<?php echo __('site_name'); ?>" class="footer-logo-img">
                    <h3 class="footer-logo-text">NoRestRP</h3>
                    <p class="footer-tagline"><?php echo __('tagline'); ?></p>
                </div>
                
                <div class="footer-links">
                    <div class="footer-section">
                        <h4><?php echo __('navigation'); ?></h4>
                        <ul>
                            <li><a href="<?php echo SITE_URL; ?>?page=home"><?php echo __('home'); ?></a></li>
                            <li><a href="<?php echo SITE_URL; ?>?page=world"><?php echo __('world'); ?></a></li>
                            <li><a href="<?php echo SITE_URL; ?>?page=forum"><?php echo __('forum'); ?></a></li>
                            <li><a href="<?php echo SITE_URL; ?>?page=help"><?php echo __('help'); ?></a></li>
                        </ul>
                    </div>
                    
                    <div class="footer-section">
                        <h4><?php echo __('account'); ?></h4>
                        <ul>
                            <?php if (isLoggedIn()): ?>
                                <li><a href="<?php echo SITE_URL; ?>?page=profile"><?php echo __('my_profile'); ?></a></li>
                                <li><a href="<?php echo SITE_URL; ?>?page=characters"><?php echo __('my_characters'); ?></a></li>
                                <li><a href="<?php echo SITE_URL; ?>?page=settings"><?php echo __('settings'); ?></a></li>
                                <li><a href="<?php echo SITE_URL; ?>?page=logout"><?php echo __('logout'); ?></a></li>
                            <?php else: ?>
                                <li><a href="<?php echo SITE_URL; ?>?page=login"><?php echo __('login'); ?></a></li>
                                <li><a href="<?php echo SITE_URL; ?>?page=register"><?php echo __('register'); ?></a></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    
                    <div class="footer-section">
                        <h4><?php echo __('social'); ?></h4>
                        <div class="social-icons">
                            <a href="#" class="social-icon"><i class="fab fa-discord"></i></a>
                            <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
                            <a href="#" class="social-icon"><i class="fab fa-facebook"></i></a>
                            <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                        </div>
                        <p class="join-community"><?php echo __('join_community'); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p class="copyright">&copy; <?php echo date('Y'); ?> <?php echo __('site_name'); ?>. <?php echo __('copyright'); ?></p>
                <ul class="footer-bottom-links">
                    <li><a href="<?php echo SITE_URL; ?>?page=terms"><?php echo __('terms_service'); ?></a></li>
                    <li><a href="<?php echo SITE_URL; ?>?page=privacy"><?php echo __('privacy_policy'); ?></a></li>
                </ul>
            </div>
        </footer>
    </div>
    
    <!-- JavaScript -->
    <script src="<?php echo SITE_URL; ?>assets/js/main.js"></script>
</body>
</html> 