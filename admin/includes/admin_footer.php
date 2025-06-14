    </div> <!-- End of Main Content Container -->

    <!-- Footer -->
    <footer class="footer mt-auto py-3 bg-dark text-white">
        <div class="container text-center">
            <p>&copy; <?php echo date('Y'); ?> Math Friends. Bảng quản trị.</p>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="../js/admin.js"></script>
    <!-- Math Editor JS -->
    <script src="../js/math_editor.js"></script>
    
    <script>
    // Process math formulas when page loads
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof processMathFormulas === 'function') {
            processMathFormulas();
        }
    });
    </script>
</body>
</html>