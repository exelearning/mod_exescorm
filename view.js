M.mod_exescormform = {};
M.mod_exescormform.init = function(Y) {
    var exescormform = Y.one('#exescormviewform');
    var cwidth = exescormplayerdata.cwidth;
    var cheight = exescormplayerdata.cheight;
    var poptions = exescormplayerdata.popupoptions;
    var launch = exescormplayerdata.launch;
    var currentorg = exescormplayerdata.currentorg;
    var sco = exescormplayerdata.sco;
    var exescorm = exescormplayerdata.exescorm;
    var launch_url = M.cfg.wwwroot + "/mod/exescorm/player.php?a=" + exescorm + "&currentorg=" + currentorg + "&scoid=" + sco + "&sesskey=" + M.cfg.sesskey + "&display=popup";
    var course_url = exescormplayerdata.courseurl;
    var winobj = null;

    poptions = poptions + ',resizable=yes'; // Added for IE (MDL-32506).

    if ((cwidth == 100) && (cheight == 100)) {
        poptions = poptions + ',width=' + screen.availWidth + ',height=' + screen.availHeight + ',left=0,top=0';
    } else {
        if (cwidth <= 100) {
            cwidth = Math.round(screen.availWidth * cwidth / 100);
        }
        if (cheight <= 100) {
            cheight = Math.round(screen.availHeight * cheight / 100);
        }
        poptions = poptions + ',width=' + cwidth + ',height=' + cheight;
    }

    // Hide the form and toc if it exists - we don't want to allow multiple submissions when a window is open.
    var exescormload = function () {
        if (exescormform) {
            exescormform.hide();
        }

        var exescormtoc = Y.one('#toc');
        if (exescormtoc) {
            exescormtoc.hide();
        }
        // Hide the intro and display a message to the user if the window is closed.
        var exescormintro = Y.one('#intro');
        exescormintro.setHTML('<a href="' + course_url + '">' + M.util.get_string('popuplaunched', 'mod_exescorm') + '</a>');
    }

    // When pop-up is closed return to course homepage.
    var exescormunload = function () {
        // Onunload is called multiple times in the EXESCORM window - we only want to handle when it is actually closed.
        setTimeout(function() {
            if (winobj.closed) {
                window.location = course_url;
            }
        }, 800)
    }

    var exescormredirect = function (winobj) {
        Y.on('load', exescormload, winobj);
        Y.on('unload', exescormunload, winobj);
        // Check to make sure pop-up has been launched - if not display a warning,
        // this shouldn't happen as the pop-up here is launched on user action but good to make sure.
        setTimeout(function() {
            if (!winobj) {
                var exescormintro = Y.one('#intro');
                exescormintro.setHTML(M.util.get_string('popupsblocked', 'mod_exescorm'));
            }}, 800);
    }

    // Set mode and newattempt correctly.
    var setlaunchoptions = function(mode) {
        if (mode) {
            launch_url += '&mode=' + (mode ? mode : 'normal');
        } else {
            launch_url += '&mode=normal';
        }

        var newattempt = Y.one('#exescormviewform #a');
        launch_url += (newattempt && newattempt.get('checked') ? '&newattempt=on' : '');
    }

    if (launch == true) {
        setlaunchoptions();
        winobj = window.open(launch_url,'Popup', poptions);
        this.target = 'Popup';
        exescormredirect(winobj);
        winobj.opener = null;
    }
    // Listen for view form submit and generate popup on user interaction.
    if (exescormform) {
        exescormform.delegate('click', function(e) {
            setlaunchoptions(e.currentTarget.getAttribute('value'));
            winobj = window.open(launch_url, 'Popup', poptions);
            this.target = 'Popup';
            exescormredirect(winobj);
            winobj.opener = null;
            e.preventDefault();
        }, 'button[name=mode]');
    }
}
