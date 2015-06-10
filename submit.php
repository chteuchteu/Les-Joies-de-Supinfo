<?php
require_once('ljs-includes.php');

$gifSubmitted = false;
$gifSubmittedError = false;
if (isset($_POST['catchPhrase']) && isset($_POST['submittedBy']) && isset($_POST['giphy_url'])) {
    $gifSubmitted = true;
    $submittedBy = $_POST['submittedBy'];
    $catchPhrase = $_POST['catchPhrase'];
    $source = $_POST['source'];

    // Create cookie with submittedBy value
    setcookie('submittedBy', $submittedBy, time()+60*60*24*30, '/'); // Expire in one month

    if (!checkCatchPhrase($catchPhrase)) {
        $gifSubmittedError = 'La phrase n\'est pas valide';
    }

    if ($gifSubmittedError === false && $_POST['giphy_url'] != '') {
        $gif = new Gif();
        $gif->catchPhrase = $catchPhrase;
        $gif->fileName = $_POST['giphy_url'];
        $gif->reportStatus = ReportState::NONE;
        $gif->gifStatus = GifState::SUBMITTED;
        $gif->permalink = getUrlReadyPermalink($catchPhrase);
        $gif->submissionDate = new DateTime();
        $gif->submittedBy = $submittedBy;
        $gif->source = $source;
        insertGif($gif);
    }
}

global $pageName;   $pageName = 'Proposer un gif';
include(ROOT_DIR.'/ljs-template/header.part.php');
?>
    <div class="content submitGif">
        <h2>Proposer un gif</h2>
        <? if ($gifSubmitted) {
            if ($gifSubmittedError === false) { ?>
                <div class="alert alert-success">Votre gif a bien été envoyé ! Il passe maintenant en modération en attente d'être publié. Merci !</div>
            <? } else { ?>
                <div class="alert alert-danger">Erreur lors de l'envoi du gif : <?= $gifSubmittedError ?></div>
            <? }
        } else { ?>
        <p>Avant de proposer un gif, veuillez vous assurer que celui-ci est conforme <a href="rulesOfTheGame.php">aux règles
                d'utilisation du service</a>.</p>
        <br />

        <form method="post" enctype="multipart/form-data">
            <?
            $submittedBy = '';
            if (isset($_POST['submittedBy']))
                $submittedBy = $_POST['submittedBy'];
            else if (isset($_COOKIE['submittedBy']))
                $submittedBy = $_COOKIE['submittedBy'];
            ?>
            <input type="text" name="submittedBy" placeholder="Proposé par (votre nom)" value="<?= $submittedBy ?>" class="submittedBy" />
            <input type="hidden" id="source" name="source" class="source" />

            <input type="text" id="catchPhraseInput" name="catchPhrase" placeholder="Titre" />
            <ul id="warnings"></ul>

            <img id="ajaxLoading" src="inc/img/ajax-loader.gif" style="visibility: hidden;" />
            <div id="giphyGifs" style="display: none;">
                <img src="inc/img/poweredByGiphy.png" class="poweredByGiphy" />
                <ul id="giphyGifsList"></ul>
            </div>

            <input type="hidden" id="giphy_url" name="giphy_url" />

            <input type="submit" value="Proposer" />
        </form>
        <? } ?>
    </div>

    <script type="application/javascript">
        $(document).ready(function() {
            var giphyGifsList = $('#giphyGifsList');

            $('#catchPhraseInput').keyup(function() {
                var text = $(this).val();

                var warningsList = [];
                // Rules
                if (text.substring(0, 'Quand'.length) != 'Quand')
                    warningsList[warningsList.length] = 'Le titre doit commencer par "Quand"';

                // No point
                if (text.substring(text.length-1) == '.')
                    warningsList[warningsList.length] = 'Le titre ne doit pas terminer par un point';

                if (text.length > 120)
                    warningsList[warningsList.length] = 'Le titre ne doit pas être trop long';
                else if (text.length < 10)
                    warningsList[warningsList.length] = 'Le titre est trop court';

                var warnings = $('#warnings');
                warnings.html('');
                for (var i=0; i<warningsList.length; i++)
                    warnings.append('<li>' + warningsList[i] + '</li>');
            });

            $('#ajaxLoading').css('visibility', 'visible');

            $.ajax({
                url: 'ljs-helper/giphyHelper.php',
                method: 'POST',
                data: {
                    action: 'getTrendingGifs'
                },
                success: function(data) {
                    var jsonData = JSON.parse(data);
                    if (jsonData.success) {
                        $('#giphyGifs').show();
                        $('#ajaxLoading').hide();

                        for (var i=0; i<jsonData.gifs.length; i++) {
                            var imageUrl = jsonData.gifs[i]['image'];
                            var sourceUrl = jsonData.gifs[i]['url'];
                            giphyGifsList.append('<li><img src="' + imageUrl + '" data-source="' + sourceUrl + '" /></li>');
                        }
                    }
                },
                error: function(data) {
                    console.log(data);
                }
            });

            // Select one gif
            giphyGifsList.on('click', 'img', function(e) {
                var img = $(this);
                $('#source').val(img.attr('data-source'));
                $('#giphy_url').val(img.attr('src'));
                giphyGifsList.find('img').removeClass('selected');
                giphyGifsList.find('img').addClass('notSelected');
                $(this).addClass('selected');

                // Avoid onclick on giphyGifsList (that's for unselecting gif)
                e.stopPropagation();
            });

            // Unselect all gifs
            giphyGifsList.click(function() {
                $('#source').val('');
                $('#giphy_url').val('');
                giphyGifsList.find('img').removeClass('selected');
                giphyGifsList.find('img').removeClass('notSelected');
            });
        });
    </script>

<? include(ROOT_DIR.'/ljs-template/footer.part.php');

function checkCatchPhrase($catchPhrase) {
    if (strlen($catchPhrase) < 10)
        return false;

    if (!str_startsWith($catchPhrase, 'Quand'))
        return false;

    return true;
}
