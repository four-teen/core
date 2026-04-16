            </div>

            <div class="content-backdrop fade"></div>
          </div>
        </div>
      </div>
    </div>

    <script src="../assets/vendor/libs/jquery/jquery.js"></script>
    <script src="../assets/vendor/libs/popper/popper.js"></script>
    <script src="../assets/vendor/js/bootstrap.js"></script>
    <script src="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="../assets/vendor/js/menu.js"></script>
    <script src="../assets/js/main.js"></script>
    <?php if (isset($extraBodyScripts) && is_string($extraBodyScripts)): ?>
      <?= $extraBodyScripts ?>
    <?php endif; ?>
  </body>
</html>
