<link href="<?=$this->getBaseUrl('application/modules/media/static/css/media.css') ?>" rel="stylesheet">

<form class="form-horizontal" method="POST" action="<?=$_SESSION['media-url-action-button'] ?><?=$this->getRequest()->getParam('id') ?>">
    <?=$this->getTokenField() ?>
    <ul class="nav nav-pills navbar-fixed-top">
        <li><a href="<?=$this->getUrl(['controller' => 'iframe', 'action' => 'upload', 'id' => $this->getRequest()->getParam('id')]) ?>"><?=$this->getTrans('upload') ?></a></li>
        <li><a href="<?=$_SESSION['media-url-media-button'] ?><?=$this->getRequest()->getParam('id') ?>"><?=$this->getTrans('media') ?></a></li>
        <li class="pull-right"><button class="btn btn-primary" name="save" type="submit" value="save"><?=$this->getTrans('add') ?></button></li>
    </ul>

    <?php if ($this->get('medias') != ''): ?>
        <div id="ilchmedia">
            <div class="container-fluid">
                <?php foreach ($this->get('medias') as $media): ?>
                    <?php if (in_array($media->getEnding(), explode(' ',$this->get('usergallery_filetypes')))): ?>
                        <div id="<?=$media->getId() ?>" class="col-lg-2 col-md-2 col-sm-3 col-xs-4 co thumb media_loader">
                            <img class="image thumbnail img-responsive"
                                 data-url="<?=$media->getUrl() ?>"
                                 src="<?=$this->getBaseUrl($media->getUrlThumb()) ?>"
                                 alt="<?=$media->getName() ?>">
                            <input type="checkbox"
                                   id="<?=$media->getId() ?>_img"
                                   class="regular-checkbox big-checkbox"
                                   name="check_image[]"
                                   value="<?=$media->getId() ?>" />
                            <label for="<?=$media->getId() ?>_img"></label>
                        </div>
                        <input type="text"
                               name="check_url[]"
                               class="hidden"
                               value="<?=$media->getUrl() ?>" />
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    <?php else: ?>
        <?=$this->getTrans('noMedias') ?>
    <?php endif; ?>
</form>

<style>
.container-fluid {
    padding-right: 15px;
    padding-left: 15px;
    margin-right: auto;
    margin-left: auto;
}
.container-fluid > [class*="col-"] {
    padding:0;
}
*, *:before, *:after {
    box-sizing: border-box;
}
</style>

<script type="text/javascript">
$(".btn").click(function(){
    window.top.$('#MediaModal').modal('hide');
    window.top.reload();
});

$(document).on("click", "img.image", function() {
    $(this).closest('div').find('input[type="checkbox"]').click();
    elem = $(this).closest('div').find('img');
    if (elem.hasClass('chacked')){
        $(this).closest('div').find('img').removeClass("chacked");
    } else {
        $(this).closest('div').find('img').addClass("chacked");
    };
});

$(document).ready(function() {
    function media_loader() { 
        var ID=$(".media_loader:last").attr("id");
        $.post("<?=$this->getUrl('admin/media/ajax/multi/type/') ?><?=$this->getRequest()->getParam('type') ?>/lastid/"+ID,
            function(data)
            {
                if (data !== "")
                {
                    $(".media_loader:last").after(data);
                }
            }
        );
    };

    $(window).scroll(function() {
        if ($(window).scrollTop() === $(document).height() - $(window).height())
        {
            media_loader();
        }
    });
});
</script>
