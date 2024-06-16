<!DOCTYPE html>
<html lang="en">
<head>
    <title>LAUNCHER</title>
    <link href="fe/css/000.css" rel="stylesheet">
    <script defer src="/fe/js/index.php"></script>

    <style>
        .div-item {
            border: #fff solid 1px;
            padding: 25px;
            margin: 25px;
        }

        figure {
            border: #0a59da solid 11px;
        }

        audio {
            /*width: 100%;*/
        }
    </style>
</head>
<body>

<div class="body-wrapper">

    <div class="div-item">

        <figure>
            <audio controls="">
                <source src="/be/multimedia/audio_shorts/001.mp3"/>
            </audio>
        </figure>

        <figure>
            <audio controls>
                <source src="/be/multimedia/audio_shorts/002.mp3"/>
            </audio>
        </figure>

        <figure>
            <audio controls>
                <source src="/be/multimedia/audio_shorts/003.mp3"/>
            </audio>
        </figure>

        <figure>
            <audio controls>
                <source src="/be/multimedia/audio_shorts/004.mp3"/>
            </audio>
        </figure>

    </div>

    <div class="div-item">
        <figure>
            <audio controls src="/be/multimedia/audio_shorts/004.mp3"></audio>
            <figcaption>
                <a
                        href="/be/multimedia/audio_shorts/004.mp3"
                >/be/multimedia/audio_shorts/004.mp3</a>
            </figcaption>
        </figure>
    </div>

    <div class="div-item">
        <figure>
            <video controls="" style="width: 100%; margin: 0 auto; text-align: center;">
                <source src="https://mironova45a.ru/uploads/NTV_2_CHE_PE.mp4" type="video/mp4">
            </video>
        </figure>
    </div>
</div>

<script>
    /**
     * Play audios one by one
     * Stop audio on another started
     */
    (function () {

        var itemAudioList = document.getElementsByTagName('audio');

        var eventHandlerOnEnded = function (e) {
            if (itemAudioList[this.nextToPlay])
                itemAudioList[this.nextToPlay].play();
        };

        var eventHandlerOnPlay = function (e) {
            for (var i = 0; i < itemAudioList.length; i++) {
                if (i === this.myCurrentListIndex) {
                    continue;
                }
                if (itemAudioList[i].pause) {
                    /**...somewhy you cannot just stop() an audio several times...*/
                    itemAudioList[i].pause();
                    itemAudioList[i].currentTime = 0;
                }

            }
        };

        var removeEventHandlers = function (e) {
            for (var i = 0; i < itemAudioList.length; i++) {
                itemAudioList[i].removeEventListener(eventHandlerOnEnded);
                itemAudioList[i].removeEventListener(eventHandlerOnPlay);
            }
        };


        for (var i = 0; i < itemAudioList.length; i++) {
            itemAudioList[i].myCurrentListIndex = i;
            if (itemAudioList[i + 1]) itemAudioList[i].nextToPlay = i + 1;
            itemAudioList[i].addEventListener('ended', eventHandlerOnEnded, false);
            itemAudioList[i].addEventListener('play', eventHandlerOnPlay, false);
        }
    })();
</script>
</body>
</html>