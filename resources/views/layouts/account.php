<?php
/**
 * @var $this Framework\View\View
 */
?>
<!DOCTYPE html>
<html>
<head>
    <title><?=(isset($title) ? $title : '')?></title>
    <!-- meta info -->
    <meta name="keywords" content="<?=(isset($meta_keywords) ? $meta_keywords : '')?>">
    <meta name="description" content="<?=(isset($meta_description) ? $meta_description : '')?>">
    <meta name="author" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Google fonts -->
    <link href="http://fonts.googleapis.com/css?family=Open+Sans:400,600,700,300" rel="stylesheet" type="text/css">
    <link href="http://fonts.googleapis.com/css?family=Roboto:400,100,300" rel="stylesheet" type="text/css">

    <!-- Bootstrap styles -->
    <link rel="stylesheet" href="/assets/css/bootstrap.css">
    <link rel="stylesheet" href="/assets/css/font-awesome.css">

    <link rel="stylesheet" href="/assets/css/common.css">
    <link rel="stylesheet" href="/assets/css/parts/header.css">
    <link rel="stylesheet" href="/assets/css/parts/cart.css">
    <link rel="stylesheet" href="/assets/css/parts/footer.css">
    <link rel="stylesheet" href="/assets/css/parts/bar.css">
    <link rel="stylesheet" href="/assets/css/product.css">

    <?php if(isset($css_files)){ ?>
        <?php foreach($css_files as $src){ ?>
            <link rel="stylesheet" href="<?=$src?>">
        <?php } ?>
    <?php } ?>
</head>
<body>

<!-- header start -->

    <?=$this->render('parts/header.php')?>

<!-- header end -->

<!-- bar start -->

    <?=$this->render('parts/bar.php', ['categories' => $categories])?>

<!-- bar end -->

<div id="wrapper">
    <section>
        <?=$this->flushBuffer('content'); ?>
    </section>
</div>

<!-- footer start -->

<?=$this->render('parts/footer.php')?>

<!-- footer start -->

<?php if(isset($js_files)){ ?>
    <?php foreach($js_files as $src){ ?>
        <script src="<?=$src?>"></script>
    <?php } ?>
<?php } ?>

<script type="text/javascript" src="/assets/Framework/Autoload.js"></script>

<?=$this->flushBuffer('footer'); ?>

</body>
</html>
