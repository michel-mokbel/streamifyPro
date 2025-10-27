      </div><!-- /.main-content -->
    </div><!-- /.d-flex -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <?php if (!empty($extraScripts)) echo $extraScripts; ?>
    <script src="./assets/js/chatbot.js"></script>
    
    <script>
      // Language Modal - Simple highlighting of current language
      (function() {
        const languageModal = document.getElementById('languageModal');
        if (!languageModal) return;

        const currentLang = document.documentElement.getAttribute('lang') || 'en';
        
        // Set active state when modal is shown
        languageModal.addEventListener('show.bs.modal', function() {
          const options = languageModal.querySelectorAll('.language-option');
          options.forEach(option => {
            const lang = option.getAttribute('data-lang');
            if (lang === currentLang) {
              option.classList.remove('btn-outline-primary');
              option.classList.add('btn-primary');
            } else {
              option.classList.remove('btn-primary');
              option.classList.add('btn-outline-primary');
            }
          });
        });
      })();
    </script>
  </body>
  </html>


