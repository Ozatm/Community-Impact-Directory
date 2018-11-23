var audioCtx = new (window.AudioContext || window.webkitAudioContext)();

if (audioCtx) {
	// // Fix iOS Audio Context by Blake Kus https://gist.github.com/kus/3f01d60569eeadefe3a1
	// MIT license
	var fixAudioContext = function (e) {
		// Create empty buffer
		var buffer = audioCtx.createBuffer(1, 1, 22050);
		var source = audioCtx.createBufferSource();
		source.buffer = buffer;

		// Connect to output (speakers)
		source.connect(audioCtx.destination);

		// Play sound
		if (source.start) {
			source.start(0);
		} else if (source.play) {
			source.play(0);
		} else if (source.noteOn) {
			source.noteOn(0);
		}

		// Remove events
		document.removeEventListener('touchstart', fixAudioContext);
		document.removeEventListener('touchend', fixAudioContext);
	};
	// iOS 6-8
	document.addEventListener('touchstart', fixAudioContext);
	// iOS 9
	document.addEventListener('touchend', fixAudioContext);


	// Thank you to MDN contributors for the underlying code setup https://developer.mozilla.org/en-US/docs/Web/API/Web_Audio_API/Visualizations_with_Web_Audio_API
	jQuery(".cid-listing").each(function (index, entry) {

		// setup canvas
		var canvas = entry.querySelector("canvas.cid-visualizer");

		if(canvas) {
			const WIDTH = canvas.width;
			const HEIGHT = canvas.height;

			var canvasCtx = canvas.getContext("2d");
			canvasCtx.clearRect(0, 0, WIDTH, HEIGHT);

			// manage link

			if(entry.querySelector("a").getAttribute("href") == "") {
				jQuery(canvas).unwrap();
			}

			// setup audio
			var source = audioCtx.createMediaElementSource(entry.querySelector("audio.cid-impact-statement-audio"));

			// setup analyser

			var analyser = audioCtx.createAnalyser();
			analyser.minDecibels = -80;
			analyser.maxDecibels = 0;
			analyser.smoothingTimeConstant = 0.7;

			if(WIDTH <= 16) {
				analyser.fftSize = 32;
			} else if (WIDTH <= 64) {
				analyser.fftSize = 64;
			} else if (WIDTH <= 64) {
				analyser.fftSize = 128;
			} else if (WIDTH <= 128) {
				analyser.fftSize = 256;
			} else if (WIDTH <= 256) {
				analyser.fftSize = 512;
			} else if (WIDTH <= 512) {
				analyser.fftSize = 1024;
			} else if (WIDTH <= 1024) {
				analyser.fftSize = 2048;
			} else if (WIDTH <= 2048) {
				analyser.fftSize = 4096;
			} else if (WIDTH <= 4096) {
				analyser.fftSize = 8192;
			} else if (WIDTH <= 8192) {
				analyser.fftSize = 16384;
			} else {
				analyser.fftSize = 32768;
			}

			source.connect(analyser);
			analyser.connect(audioCtx.destination);

			//creating the visualization

			var drawVisual;		  
			var bufferLengthAlt = analyser.frequencyBinCount;
			var dataArrayAlt = new Uint8Array(bufferLengthAlt);

			var drawAlt = function() {
				drawVisual = requestAnimationFrame(drawAlt);

				analyser.getByteFrequencyData(dataArrayAlt);
				canvasCtx.clearRect(0, 0, WIDTH, HEIGHT);

				for(var i = 0; i < bufferLengthAlt; i++) {
					var barHeight = dataArrayAlt[i] * HEIGHT / 150;

					canvasCtx.fillStyle = 'rgb(50,50,' + (100 + barHeight) + ')';
					canvasCtx.fillRect((WIDTH / 2) - (i / 3), (HEIGHT - (barHeight / 1.5)) / 2, 1, barHeight / 1.5);
					canvasCtx.fillRect((WIDTH / 2) + (i / 3), (HEIGHT - (barHeight / 1.5)) / 2, 1, barHeight / 1.5);
					//canvasCtx.fillRect(i, (HEIGHT - barHeight) / 2, 1, barHeight);
				}

			};

			drawAlt();
		}
	});

	jQuery("canvas.cid-visualizer").css("opacity", "0");
}

jQuery("audio.cid-impact-statement-audio").on("play", function() {

	// restrict audio players to one at a time
	jQuery("audio.cid-impact-statement-audio").not(this).each(function(index, audio) {
		audio.pause();
		jQuery(this).closest(".cid-listing-body").find(".cid-listing-thumbnail img").animate({opacity: 1});
		jQuery(this).closest(".cid-listing-body").find("canvas.cid-visualizer").animate({opacity: 0});
	});

	jQuery(this).closest(".cid-listing-body").find(".cid-listing-thumbnail img").animate({opacity: 0.25});
	jQuery(this).closest(".cid-listing-body").find("canvas.cid-visualizer").animate({opacity: 1});	
});

jQuery("audio.cid-impact-statement-audio").on("pause", function() {
	jQuery(this).closest(".cid-listing-body").find(".cid-listing-thumbnail img").animate({opacity: 1});
	jQuery(this).closest(".cid-listing-body").find("canvas.cid-visualizer").animate({opacity: 0});
});