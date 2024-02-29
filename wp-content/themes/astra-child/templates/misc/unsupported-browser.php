<div id="unsupported-message">
    <a href="#unsupported-modal" id="unsupported-overlay"></a>
    <div id="unsupported-modal" role="alertdialog" aria-labelledby="unsupported-title">
        <h2 id="unsupported-title">⚠ Nicht Unterstützter Browser ⚠</h2>
        <p><strong><?php echo $args['blogname'] ?></strong> funktioniert wahrscheinlich nicht sonderlich gut mit Internet Explorer <span id="ie_version"></span>. Wir unterstützen lediglich jüngere Browser Versionen wie Chrome, Firefox, Safari, und Edge. Benutze diesen hier auf eigenes Risiko! </p>
        <p>Falls du der Meinung bist diese Mitteilung sei ein Fehler, <a href="mailto:support@ha-lehmann.at">schreibe uns eine E-Mail an <?php echo $args['email']; ?></a> und teile uns Modell und Version deines Browsers mit.</p>
        <button id="unsupported-close">Verwerfen &amp; Trotzdem versuchen</button>
    </div>
    <a href="/#unsupported-browser" id="unsupported-banner" role="alert">
        <strong> ⚠ Nicht Unterstützter Browser ⚠ </strong>
        Army-Shop Lehmann wird nicht zufriedenstellend funktionieren mit diesem Browser. <u>Mehr erfahren</u>
    </a>
    <style>
        #unsupported-overlay,
        #unsupported-modal {
            display: block;
            position: absolute;
            position: fixed;
            left: 0;
            right: 0;
            z-index: 9999;
            background: #000;
            color: #D5D7DE;
        }

        #unsupported-modal a {
            color: #76daff;
        }

        #unsupported-overlay {
            top: 0;
            bottom: 0;
            opacity: 0.7;
        }

        #unsupported-modal {
            top: calc(100vh - 80%);
            margin: auto;
            width: 90%;
            max-width: 690px;
            max-height: 90%;
            padding: 40px 20px;
            box-shadow: 0 10px 30px #000;
            text-align: center;
            overflow: hidden;
            overflow-y: auto;
            border: solid 7px #ffdd40;
        }

        #unsupported-message :last-child {
            margin-bottom: 0;
        }

        #unsupported-message h2 {
            font-family: 'Telefon', Sans-Serif;
            font-size: 34px;
            color: #FFF;
        }

        #unsupported-message h3 {
            font-size: 20px;
            color: #FFF;
        }

        #unsupported-close {
            display: inline-block;
            padding: 10px 20px;
            background: #ffdd40;
            color: #000 !important;
            border: none;
            border-radius: 5px;
            font: inherit;
            font-size: 16px;
            cursor: pointer;
        }

        #unsupported-close:hover {
            background: #ffd40d;
        }

        body.hide-unsupport {
            padding-top: 24px;
        }

        body.hide-unsupport #unsupported-message {
            visibility: hidden;
        }

        #unsupported-banner {
            display: block;
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            background: #ffdd40;
            color: #000;
            font-size: 14px;
            padding: 2px 5px;
            line-height: 1.5;
            text-align: center;
            visibility: visible;
            z-index: 199;
        }

        #unsupported-banner a {
            color: #000;
            text-decoration: underline;
        }

        @media (max-width: 800px),
        (max-height: 500px) {
            #unsupported-message .modal p {
                /* font-size: 12px; */
                line-height: 1.2;
                margin-bottom: 1em;
            }

            #unsupported-modal {
                position: absolute;
                top: 20px;
            }

            #unsupported-message h1 {
                font-size: 22px;
            }

            body.hide-unsupport {
                padding-top: 0px;
            }

            #unsupported-banner {
                position: static;
            }

            #unsupported-banner strong,
            #unsupported-banner u {
                display: block;
            }
        }
    </style>
    <script>
        document.getElementById('unsupported-close').addEventListener('click', function() {
            document.body.className += ' hide-unsupport';
        });
        document.getElementById('unsupported-banner').addEventListener('click', function() {
            document.body.className = document.body.className.replace(' hide-unsupport', '');
        });
        document.getElementById('ie_version').textContent = __browser.version;
    </script>
</div>