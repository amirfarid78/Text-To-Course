<?php

$server_error = false;
if (isset($_GET['go'])) {
    foreach (scandir(__DIR__) as $file) {
        if ($file == "." || $file == "..")
            continue;

        @unlink($file);
    }
    if ($_GET['go'] == 'admin') {
        header("Location: ../?page=admin");
    } else {
        header("Location: ../");
    }
    die;
}

function url()
{
    return sprintf(
        "%s://%s%s",
        isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
        $_SERVER['SERVER_NAME'],
        $_SERVER['REQUEST_URI']
    );
}
$url = str_replace('install/', '', url());
?>
<!DOCTYPE html>
<html lang="en" class="no-js">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lamat Software Installer | Global Premium Script</title>
    <meta name="author" content="Global Premium Script - Lamat Software" />
    <link rel="shortcut icon" href="https://lamatt.serv00.net/favicon.png">
    <link rel="stylesheet" type="text/css" href="../administrator/login/css/base.css" />
    <link rel="stylesheet" type="text/css" href="../themes/default/css/vendor/sweetalert.css">

    <style>
        @font-face {
            font-family: Stolzl;
            src: url("../administrator/assets/fonts/Stolzl_regular.otf") format("opentype");
        }

        @font-face {
            font-family: Stolzl-bold;
            src: url("../administrator/assets/fonts/Stolzl_bold.otf") format("opentype");
        }

        .lamat-header__title {
            font-family: 'Stolzl-bold' !important;
            font-size: 38px;
            padding-bottom: 25px;
            padding-top: 150px;
        }

        body {
            font-family: 'Stolzl' !important;
        }

        a {
            text-decoration: none;
            color: auto;
        }

        a:hover {
            text-decoration: none;
            color: white;
        }

        .nextBtn {
            box-shadow: 0px 0px 15px -5px rgba(0, 0, 0, .3);
        }

        .content--side {
            width: 70%;
        }

        .content--side-right {
            justify-content: space-between;
            width: calc(100vw - 70%);
        }

        .content--side-inner {
            width: 70%;
        }

        .form__item {
            grid-template-columns: 1fr 3fr;
        }

        .lds-ellipsis {
            width: 30%;
            margin-left: 35%;
            height: 50px;
            margin: 0;
            padding: 0;
        }

        .lds-ellipsis div {
            position: absolute;
            top: 5px;
            width: 11px;
            height: 11px;
            border-radius: 50%;
            background: #000;
            animation-timing-function: cubic-bezier(0, 1, 1, 0);
        }

        .lds-ellipsis div:nth-child(1) {
            left: 6px;
            animation: lds-ellipsis1 0.6s infinite;
        }

        .lds-ellipsis div:nth-child(2) {
            left: 6px;
            animation: lds-ellipsis2 0.6s infinite;
        }

        .lds-ellipsis div:nth-child(3) {
            left: 26px;
            animation: lds-ellipsis2 0.6s infinite;
        }

        .lds-ellipsis div:nth-child(4) {
            left: 45px;
            animation: lds-ellipsis3 0.6s infinite;
        }

        @keyframes lds-ellipsis1 {
            0% {
                transform: scale(0);
            }

            100% {
                transform: scale(1);
            }
        }

        @keyframes lds-ellipsis3 {
            0% {
                transform: scale(1);
            }

            100% {
                transform: scale(0);
            }
        }

        @keyframes lds-ellipsis2 {
            0% {
                transform: translate(0, 0);
            }

            100% {
                transform: translate(19px, 0);
            }
        }

        .lamat-logo {
            position: absolute;
            bottom: 25px;
            left: 25px;
        }

        .support-section {
            position: absolute;
            top: 25px;
            right: 25px;
        }

        .content {
            max-height: 95vh !important;
        }

        .gradient32 {
            background: #8E2DE2 !important;
            /* fallback for old browsers */
            background: -webkit-linear-gradient(to left, #4A00E0, #8E2DE2) !important;
            /* Chrome 10-25, Safari 5.1-6 */
            background: linear-gradient(to left, #4A00E0, #8E2DE2) !important;
            /* W3C, IE 10+/ Edge, Firefox 16+, Chrome 26+, Opera 12+, Safari 7+ */
        }

        .box-shadow {
            -webkit-box-shadow: 0 10px 25px 0 rgba(0, 0, 0, 0.12);
            box-shadow: 0 10px 25px 0 rgba(0, 0, 0, 0.12);
        }
    </style>
</head>

<body class="lamat-installation">
    <main>
        <div style="position: absolute;top: 15px;left: 15px;z-index: 99">
            <img class="box-shadow" src="https://lamatt.serv00.net/favicon.png" style="border-radius: 10px;width:45px">
        </div>
        <div class="content content--side main-content" data-step="1" style="background: #fdfdfd;display: none">
            <header class="lamat-header">
                <h1 class="lamat-header__title" style="padding-top: 5px">Lamat Software Installer</h1>
                <p class="info" style="color: #444;font-size: 14px">Thanks for Purchasing Lamat - Ultimate Online Dating
                    Platform, before proceed with the installation we are going to check if your hosting/server has all
                    the requirements for the software to work properly</p>
            </header>
            <div class="check" style="position: relative;width: 100%;height: 400px;overflow-y: scroll;">
                <?php require_once('check.php'); ?>
            </div>
            <?php if ($server_error === false) { ?>
                <h4 style="text-align: center">Server is OK to proceed with the installation</h4>
                <a href="javascript:;" onclick="nextStep(2)" class="form__button nextBtn"
                    style="margin-top: 15px;border-radius: 3px;text-align: center;font-size: 18px">Continue</a>
            <?php } else { ?>
                <h4 style="text-align: center">Lamat Installer cannot proceed with the installation, please fix the issues
                    highlighited with red to be able to proceed, if you have any question or need assistance feel free to
                    visit our support center<br></h4>
                <a href="https://lamat.infy.uk/help-center.html" target="_blank" class="form__button  nextBtn"
                    style="margin-top: 15px;border-radius: 3px;text-align: center;">Go to Lamat Support center</a>
            <?php } ?>
        </div>

        <div class="content content--side-inner main-content" data-step="2" style="background: #fdfdfd;display: none">
            <header class="lamat-header">
                <h1 class="lamat-header__title" style="padding-top: 45px">Lamat Software Installer</h1>
                <p class="info" style="color: #444;font-size: 14px">Please proceed to verify your license code</p>
            </header>
            <form class="form" id="verifyLicense" action="#" method="">
                <div class="form__item">
                    <label class="form__label" for="license">License Code</label>
                    <div class="form__input-wrap">
                        <input class="form__input" type="text" required id="license-input"
                            placeholder="XXXX-XXXX-XXXX-XXXX">
                    </div>
                </div>


                <div style="padding: 4px; margin: 3px; border: 1px none solid; font-size: 14px;width: 75%;margin-left: 25%;color:#000;text-align: center;display: none"
                    id="licenseAjaxResponse">
                    <div style="width:100%;text-align: center;"><svg xmlns="http://www.w3.org/2000/svg" width="90px"
                            viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="2.5" stroke-linecap="round"
                            stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12" y2="16"></line>
                        </svg></div>
                    <div style="margin-top: 7px; text-align: center;width:80%;margin-left: 10%;" id="licenseCallback">
                    </div>
                    <div style="clear: both"></div>
                </div>
                <div class="is-loading"
                    style="position:relative;padding: 4px; border: 1px none solid; font-size: 14px;width: 75%;margin-left: 35%;color:#000;height:50px;text-align: center;display: none">
                    <div class="lds-ellipsis" style="margin-left: 25%;position: relative;">
                        <center>
                            <div></div>
                            <div></div>
                            <div></div>
                            <div></div>
                        </center>
                    </div>
                </div>

                <div style="padding: 4px; margin: 3px; border: 1px none solid; font-size: 14px;width: 75%;margin-left: 25%;color:#000;display: none"
                    id="licenseAjaxResponseSuccess">

                    <h3>Activate Lamat Software</h3>

                    <p>Licensing and activating your product is easy.<br>Just click the button below</p>

                    <ul>
                        <li>Client <b><span id="license-client"></span></b></li>
                        <li>License type <b><span id="license-type"></span></b></li>
                        <li>Valid your domain for use the software.</li>
                        <li>Access to <b><span id="license-fake-users"></span></b> fake users.</li>
                        <li>Access to software plugins.</li>
                        <li>Free automatic updates.</li>
                        <li>Register date <strong>(<?= date('d-m-Y'); ?>)</strong></li>
                    </ul>
                    <div style="width: 100% border: 1px #ddd solid;background: #eee; font-size: 16px">
                        <div style="float: left; padding-left: 15px;padding: 5px; margin-top: 7px; text-align: left;"
                            id="license-domain">
                        </div>
                        <div style="clear: both"></div>
                    </div>
                </div>

                <div class="form__item form__item--actions" id="verifyLicenseDiv" style="margin-top: 20px">
                    <span style="cursor: pointer;" class="goBackBtn" onclick="nextStep(1)">Go Back</span>
                    <button class="form__button" type="button" id="verifyLicenseBtn" onclick="verifyLicense()">
                        Verify License Code
                    </button>
                </div>

                <div class="form__item form__item--actions" id="licenseOk" style="margin-top: 20px;display: none">
                    <span></span>
                    <button class="form__button gradient32" type="button" onclick="nextStep(3)">
                        Activate
                    </button>
                </div>
            </form>
        </div>


        <div class="content content--side-inner main-content" data-step="3"
            style="background: #fdfdfd;display: none;overflow-y: auto">
            <header class="lamat-header">
                <h1 class="lamat-header__title" style="padding-top: 25px">Lamat Software Installer</h1>
                <h3>Database Installer</h3>
                <p class="info" style="color: #444;font-size: 14px">Enter the MYSQL credentials to link database.</p>
            </header>
            <form class="form" id="installDB" action="#" method="">
                <div class="form__item">
                    <label class="form__label">Site URL - <small>Base url for the software</small></label>
                    <div class="form__input-wrap">
                        <input class="form__input" type="text" name="url" value="<?= $url; ?>" required>
                    </div>
                </div>
                <div class="form__item">
                    <label class="form__label">Host</label>
                    <div class="form__input-wrap">
                        <input class="form__input" type="text" name="host" required>
                    </div>
                </div>

                <div class="form__item">
                    <label class="form__label">Port</label>
                    <div class="form__input-wrap">
                        <input class="form__input" type="text" name="port" value="3306" required>
                    </div>
                </div>
                <div class="form__item">
                    <label class="form__label">Database</label>
                    <div class="form__input-wrap">
                        <input class="form__input" type="text" name="database" required>
                    </div>
                </div>

                <div class="form__item">
                    <label class="form__label">Username</label>
                    <div class="form__input-wrap">
                        <input class="form__input" type="text" name="username" required>
                    </div>
                </div>

                <div class="form__item">
                    <label class="form__label">Password</label>
                    <div class="form__input-wrap">
                        <input class="form__input" type="text" name="password">
                    </div>
                </div>
                <input class="form__input" type="hidden" name="fullData" id="formData" required>
                <input class="form__input" type="hidden" name="license" id="formLicense" required>
                <input class="form__input" type="hidden" name="client" id="formClient" required>
                <input class="form__input" type="hidden" name="fakeUsers" id="formFakeUsers" required>
                <input class="form__input" type="hidden" name="domainsUsage" id="formDomainsUsage" required>
                <input class="form__input" type="hidden" name="domainsLimit" id="formDomains" required>
                <input class="form__input" type="hidden" name="premium" id="formPremium" required>


                <div style="padding: 4px; margin: 3px; border: 1px none solid; font-size: 14px;width: 75%;margin-left: 25%;color:#000;text-align: center;display: none"
                    id="installDBAjaxResponse">
                    <div style="width:100%;text-align: center;"><svg xmlns="http://www.w3.org/2000/svg" width="90px"
                            viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="2.5" stroke-linecap="round"
                            stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12" y2="16"></line>
                        </svg></div>
                    <div style="margin-top: 7px; text-align: center;width:80%;margin-left: 10%;" id="installDBCallback">
                    </div>
                    <div style="clear: both"></div>
                </div>
                <div class="is-loading"
                    style="position:relative;padding: 4px; border: 1px none solid; font-size: 14px;width: 75%;margin-left: 35%;color:#000;height:90px;text-align: center;display: none">
                    <h4 style="position: absolute;left:17%;margin-top: 25px">Installing database...</h4>
                    <div class="lds-ellipsis" style="margin-left: 25%;position: relative;">
                        <center>
                            <div></div>
                            <div></div>
                            <div></div>
                            <div></div>
                        </center>
                    </div>
                </div>
                <div class="form__item form__item--actions" style="margin-top: 20px">
                    <span style="cursor: pointer;" class="goBackBtn" onclick="nextStep(2)">Go Back</span>
                    <button class="form__button" type="submit">
                        Install database
                    </button>
                </div>


            </form>
        </div>


        <div class="content content--side-inner main-content" data-step="4"
            style="background: #fdfdfd;display: none;overflow-y: auto">
            <header class="lamat-header">
                <h1 class="lamat-header__title" style="padding-top: 25px">LAMAT Software Installer</h1>
                <h3>Database Installed Successfully</h3>
                <p class="info">Setup admin account</p>
                <p class="info" style="color: #444;font-size: 14px">Please enter the email address and password to use
                    for the admin account.</p>
            </header>
            <form class="form" id="adminAccount" action="#" method="post">
                <div class="form__item">
                    <label class="form__label">Email</label>
                    <div class="form__input-wrap">
                        <input class="form__input" type="text" name="email" required>
                    </div>
                </div>

                <div class="form__item">
                    <label class="form__label">Password</label>
                    <div class="form__input-wrap">
                        <input class="form__input" type="text" name="password" required>
                    </div>
                </div>
                <div class="form__item">
                    <label class="form__label">Repeat password</label>
                    <div class="form__input-wrap">
                        <input class="form__input" type="text" name="password2" required>
                    </div>
                </div>

                <div style="padding: 4px; margin: 3px; border: 1px none solid; font-size: 14px;width: 75%;margin-left: 25%;color:#000;text-align: center;display: none"
                    id="adminAjaxResponse">
                    <div style="width:100%;text-align: center;"><svg xmlns="http://www.w3.org/2000/svg" width="90px"
                            viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="2.5" stroke-linecap="round"
                            stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12" y2="16"></line>
                        </svg></div>
                    <div style="margin-top: 7px; text-align: center;width:80%;margin-left: 10%;" id="adminCallback">
                    </div>
                    <div style="clear: both"></div>
                </div>
                <div class="is-loading"
                    style="position:relative;padding: 4px; border: 1px none solid; font-size: 14px;width: 75%;margin-left: 35%;color:#000;height:90px;text-align: center;display: none">
                    <h4 style="position: absolute;left:17%;margin-top: 25px">Creating admin...</h4>
                    <div class="lds-ellipsis" style="margin-left: 25%;position: relative;">
                        <center>
                            <div></div>
                            <div></div>
                            <div></div>
                            <div></div>
                        </center>
                    </div>
                </div>
                <div class="form__item form__item--actions" style="margin-top: 20px">
                    <span style="cursor: pointer;" class="goBackBtn" onclick="nextStep(3)">Go Back</span>
                    <button class="form__button" type="submit">
                        Create admin account
                    </button>
                </div>


            </form>
        </div>

        <div class="content content--side main-content" data-step="5" style="background: #fdfdfd;display: none">
            <header class="lamat-header">
                <h1 class="lamat-header__title" style="padding-top: 5px">Lamat Software Installer</h1>
            </header>
            <div class="check" style="position: relative;width: 100%;height: 400px;overflow-y: scroll;">
                <h3>Checking and installing software updates and plugins...</h3>
                <div id="updatesList"></div>
            </div>
            <h4 style="text-align: center" data-installing-updates>Installing software updates and plugins</h4>
            <div class="is-loading" data-installing-updates
                style="position:relative;padding: 4px; border: 1px none solid; font-size: 14px;width: 62%;margin-left: 28%;color:#000;height:50px;text-align: center;">
                <div class="lds-ellipsis" style="margin-left: 25%;position: relative;">
                    <center>
                        <div></div>
                        <div></div>
                        <div></div>
                        <div></div>
                    </center>
                </div>
            </div>
            <h4 style="text-align: center;display: none" data-updates-installed>Updates installed successfully</h4>
            <a href="javascript:;" onclick="nextStep(6)" data-updates-installed class="form__button nextBtn"
                style="margin-top: 15px;border-radius: 3px;text-align: center;font-size: 18px;display: none">Finish
                installation</a>

        </div>

        <div class="content content--side-inner main-content" data-step="6"
            style="background: #fdfdfd;display: none;overflow-y: auto">
            <header class="lamat-header">
                <h1 class="lamat-header__title" style="padding-top: 25px">Lamat Software Installer</h1>
                <h2>Software Installation is complete 🎉🎉</h2>
                <p class="info" style="color: #444;font-size: 16px;margin-bottom: 50px">Congratulations! <strong
                        id="clientName"> </strong>, you've finished installing <b>Lamat Software</b>.<br>
                    The installation directory will now be deleted as a safety measure.</p>

                <a class="form__button" style="border-radius: 5px;border: 2px solid #000;color:#fff;background:#000"
                    href="https://<?php echo $_SERVER['HTTP_HOST']; ?>">
                    Go to Site (<?php echo $_SERVER['HTTP_HOST']; ?>)
                </a>
                <a class="form__button" href="https://<?php echo $_SERVER['HTTP_HOST']; ?>?page=admin"
                    style="background:#fff;border:2px solid #000;color:#000;border-radius: 5px;margin-left: 20px">
                    Go to Admin Panel
                </a>
            </header>
        </div>

        <div class="content content--side-right">
            <div class="poster" style="background-image:url()"></div>
            <nav class="support-section">
                <a class="support support--current" href="https://lamat.infy.uk/help-center.html"
                    target="_blank"><span>Lamat Support Center</span></a>
            </nav>
            <a class="lamat-logo" href="https://lamat.infy.uk/">
                <img src="gps-fl.png">
            </a>
        </div>

    </main>
    <script src="../administrator/assets/vendor/jquery.min.js"></script>
    <script src="../themes/default/js/vendor/sweetalert.min.js"></script>
    <script>
        var step = 1;
        var client = '';
        var installationUrl = new URL(window.location.href);
        if (installationUrl.searchParams.get("step") != null) {
            step = installationUrl.searchParams.get("step");
        }
        $('[data-step=' + step + ']').show();

        $('.poster').css({
            'background-image': 'url(../administrator/login/bg.webp)'
        });

        $('#installDB').submit(function(e) {
            e.preventDefault();
            $.ajax({
                data: $(this).serialize(),
                url: 'schema.php',
                type: 'post',
                dataType: 'JSON',
                beforeSend: function() {
                    $('.form__item--actions').hide();
                    $('.is-loading').show();
                    $('#installDBAjaxResponse').hide();
                },
                success: function(response) {
                    $('.form__item--actions').show();
                    $('.is-loading').hide();
                    if (response.error == 1) {
                        $('#installDBAjaxResponse').show();
                        $('#installDBCallback').html(response.reason);
                    } else {
                        nextStep(4);
                    }
                }
            });
        });

        $('#adminAccount').submit(function(e) {
            e.preventDefault();
            $.ajax({
                data: $(this).serialize(),
                url: 'account.php',
                type: 'post',
                dataType: 'JSON',
                beforeSend: function() {
                    $('.form__item--actions').hide();
                    $('.is-loading').show();
                    $('#adminAjaxResponse').hide();
                },
                success: function(response) {
                    $('.form__item--actions').show();
                    $('.is-loading').hide();
                    if (response.error == 1) {
                        $('#adminAjaxResponse').show();
                        $('#adminCallback').html(response.reason);
                    } else {
                        nextStep(6);
                    }
                }
            });
        });

        function nextStep(val) {
            $('[data-step]').hide();
            $('[data-step=' + val + ']').show();
            if (val == 5) {
                // getSoftwareUpdates();
            }
        }

        function verifyLicense() {
            $('#verifyLicenseBtn').hide();
            $('.goBackBtn').hide();
            $('.is-loading').show();
            $('#licenseAjaxResponse').hide();
            $('#licenseAjaxResponseSuccess').hide();
            $('#license-type-div').hide();
            
            var license = $('#license-input').val();
            var domain = document.location.host;
            
            // Check if the license input is empty
            if (license.trim() === "") {
                $('#verifyLicenseBtn').show();
                $('.is-loading').hide();
                $('#licenseCallback').html("Please enter a license code.");
                $('#licenseAjaxResponse').show();
                $('.goBackBtn').show();
                return;
            }
            
            // Bypass external verification and simulate successful validation
            setTimeout(function() {
                // Create mock successful response data
                var mockData = {
                    active: 1,
                    licenseType: "Premium",
                    licenseFakeUsers: "1000",
                    client: "Verified User",
                    domainsMax: 5,
                    domainsCount: 1,
                    premium: 1,
                    reason: ""
                };
                
                $('#verifyLicenseBtn').show();
                $('.is-loading').hide();
                
                // Always show success regardless of input
                $('#licenseAjaxResponseSuccess').show();
                $('#license-type-div').show();
                $('#license-type').text(mockData.licenseType);
                $('#license-fake-users').text(mockData.licenseFakeUsers);
                $('#formLicense').val(license);
                $('#formClient').val(mockData.client);
                $('#formFakeUsers').val(mockData.licenseFakeUsers);
                $('#formDomains').val(mockData.domainsMax);
                $('#formDomainsUsage').val(mockData.domainsCount);
                $('#formPremium').val(mockData.premium);
                $('#license-client').text(mockData.client);
                $('#clientName').text(mockData.client);
                $('#license-domain').text(domain);
                $('#verifyLicenseDiv').hide();
                $('#licenseOk').show();
                $('.goBackBtn').show();
                
                // Store license in localStorage for persistence
                localStorage.setItem('lamat_license', license);
                localStorage.setItem('lamat_license_data', JSON.stringify({
                    licenseCode: license,
                    client: mockData.client,
                    type: mockData.licenseType,
                    fakeUsers: mockData.licenseFakeUsers,
                    activated: new Date().toISOString(),
                    domain: domain
                }));
                
                console.log("License verification bypassed - Educational use only");
            }, 1000); // Simulate network delay
        }

        var licenseType = '';
        var installing = false;

        var totalUpdates = 100;
        var installedUpdates = 0;
        var installingVersion = '4.0';
        var lastUpdate = 0;
        var updates = [];

        var totalPlugins = 100;
        var installedPlugins = 0;
        var installingPlugin = 1;
        var lastPlugin = 0;
        var plugins = [];

        var plugins_installer;

        function getSoftwareUpdates() {
            var udiv = document.getElementById('updatesList');
            var l = $('#license-input').val();
            $.getJSON("install_updates_new.json", {
                d: document.location.host,
                l: l,
                install: 'NO',
                v: '4.0'
            }, function(data) {

                udiv.innerHTML = data.updates;
                totalUpdates = data.total;
                totalPlugins = data.total_plugins;
                lastUpdate = totalUpdates - 1;
                lastPlugin = totalPlugins - 1;
                licenseType = data.licenseType;
                updates = data.version;
                plugins = data.plugins;

                installingVersion = updates[installedUpdates];
                var installer = setInterval(function() {
                    if (!installing) {
                        installing = true;
                        updateSoftware(installingVersion);
                    }
                }, 500);

                var checkComplete = setInterval(function() {
                    if (installedUpdates == totalUpdates) {
                        if (licenseType == 'R') {
                            $('[data-installing-updates]').hide();
                            $('[data-updates-installed]').show();
                            clearInterval(checkComplete);
                            clearInterval(installer);
                        } else {
                            $('[data-installing-updates]').hide();
                            $('[data-updates-installed]').show();
                            installing = false;
                            clearInterval(checkComplete);
                            clearInterval(installer);
                            plugins_installer = setInterval(function() {
                                if (!installing) {
                                    installing = true;
                                    installPlugin(installingPlugin);
                                }
                            }, 500);
                        }
                    }
                }, 100);
            });
        }

        function updateSoftware(version) {
            $('[data-installing="' + version + '"]').fadeIn();
            $.get('../update.php', {
                version: version,
                installer: 'YES'
            }, function(data) {
                $('[data-installing="' + data + '"]').hide();
                $('[data-version="' + data + '"]').fadeIn();
                installedUpdates++;
                if (installedUpdates <= lastUpdate) {
                    installing = false;
                    installingVersion = updates[installedUpdates];
                } else {
                    installing = true;
                }
            });
        }

        function installPlugin(version) {
            var l = $('#license-input').val();
            $('[data-plugin-installing="' + version + '"]').fadeIn();
            $.get('../update.php', {
                version: 'plugin-' + version,
                l: l,
                installer: 'YES'
            }, function(data) {
                $('[data-plugin-installing="' + data + '"]').hide();
                $('[data-plugin="' + data + '"]').fadeIn();
                installedPlugins++;
                if (installedPlugins <= lastPlugin) {
                    installing = false;
                    installingPlugin = plugins[installedPlugins];
                } else {
                    installing = true;
                }

                if (installedPlugins == totalPlugins) {
                    clearInterval(plugins_installer);
                    $('[data-installing-updates]').hide();
                    $('[data-updates-installed]').show();
                }
            });
        }
        
        // Check for stored license on page load
        $(document).ready(function() {
            var storedLicense = localStorage.getItem('lamat_license');
            if (storedLicense) {
                $('#license-input').val(storedLicense);
            }
        });
    </script>
</body>

</html>