<?php 
    require_once '../backends/main.php';
    /**
     * Remove # if you want to have a modified title
     * Default is the file name without ['-','_']
    */

    #$title 
?>
<!DOCTYPE html>
<html lang="en">
    <?php include ROOT_PATH . '/components/head.php'; ?>
    <body data-base="<?php echo BASE; ?>">
        <?php include ROOT_PATH . '/components/header.php'; ?>
    <!-- Main Dashboard Content -->
    <main class="dashboard-content">
    </main>
    <!-- END Main Dashboard Content -->
    <!-- Ending All Main Content -->
    </main> 
    </div> 
    <!-- End Header -->
    <!-- REPLACE THIS FOR HIDDEN MODALS AND WHATNOT -->
    <?php include ROOT_PATH . '/components/footer.php'; ?>
    <!-- Modified JavaScript Section -->
</body>
</html>