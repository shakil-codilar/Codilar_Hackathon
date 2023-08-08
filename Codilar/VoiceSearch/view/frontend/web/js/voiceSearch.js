define([
    'jquery',
    'domReady!'
], function ($) {
    'use strict';
    var $voiceSearchTriggerDesktop = $("#voice-search-trigger-desktop");
    var $voiceSearchTriggerMobile = $("#voice-search-trigger-mobile");
    var $miniSearchForm = $("#search_mini_form");
    var $searchInput = $("#search");
    window.SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

    function _parseTranscript(e) {
        return Array.from(e.results).map(result => result[0]).map(result => result.transcript).join('')
    }

    function _transcriptHandler(e) {
        $searchInput.val(_parseTranscript(e));
        if (e.results[0].isFinal) {
            $miniSearchForm.submit();
        }
    }

    if (window.SpeechRecognition) {
        var recognition = new SpeechRecognition();
        recognition.interimResults = true;
        recognition.addEventListener('result', _transcriptHandler);
    } else {
        alert("Speech Recognition is not supported in your browser or it has been disabled.");
    }

    function startListening(e) {
        e.preventDefault();
        $(".voicesearch-trigger").removeClass('voicesearch_mic').addClass('voicesearch_mic_on');
        $searchInput.attr("placeholder", "Listening...");
        recognition.start();
    }

    return function() {
        $voiceSearchTriggerDesktop.on('click touch', startListening);
        $voiceSearchTriggerMobile.on('click touch', startListening);
    }
});
