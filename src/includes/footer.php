    </div> <!-- .container -->
    
    <?php if (isset($additionalJS)): ?>
        <?php foreach ($additionalJS as $js): ?>
            <script src="<?php echo $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <?php if (isset($additionalFooterContent)): ?>
        <?php echo $additionalFooterContent; ?>
    <?php endif; ?>
</body>
</html>
