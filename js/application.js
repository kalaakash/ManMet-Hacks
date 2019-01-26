/** namespace Backend */
var Backend = (function() {

	return {
		getFirstSlide: function(callback) {
			callback(new Slides.RangeSlide('Why do they say it be like it do?', 1, 10));
			// callback(new Slides.SelectionSlide('First Slide', ['Hello', 'World', 'This is a button with a lot of text']));
		},
		submitAnswer: function(answer, callback) {
			callback(new Slides.SelectionSlide('Marmite', ['But pa might not', 'Eh?']));
			// callback(new Slides.RangeSlide('Why do they say it be like it do?', 1, 10));
		}
	};

})();

/**
 * namespace Slides
 *
 * This namespace contains classes and static methods to handle
 * 'slides'. Slides are named after the same concept in slideshows
 * and presentations, particularly due to their 'sliding' animation.
 * Slides fill the entire screen and may be hot-swapped out.
 */
var Slides = (function() {

	// Local consts
	var SLIDE_ANIMATION_TIME = 700,
			MAIN_ELEM = document.getElementById('main');

	// Local vars
	var lastSlide = null;

	/** enum SlideType */
	var SlideType = {
		//QUESTION_SLIDE: 1,
		SELECTION_SLIDE: 1,
		RANGE_SLIDE: 2
	};

	/** Slide::constructor(SlideType type) */
	var Slide = function(type) {
		this.type = type;
	};

	/** HTMLElement Slide::createElement() */
	Slide.prototype.createElement = function() {
		var container = document.createElement('div');
		container.setAttribute('class', 'slide');

		var center = document.createElement('div');
		center.setAttribute('class', 'slide__center');
		container.appendChild(center);

		return container;
	};

	/**
	 * SelectionSlide::constructor(Array options)
	 * 
	 * `options` is an array of strings, each string
	 * corresponding to the text that will be displayed to
	 * the user.
	 */
	var SelectionSlide = function(question, options) {
		// Call super constructor
		Slide.call(this, SlideType.SELECTION_SLIDE);

		this.question = question;
		this.options = options;
	}

	/** SelectionSlide extends Slide */
	SelectionSlide.prototype = Object.create(Slide.prototype);

	/** SelectionSlide::createElement overrides Slide::createElement */
	SelectionSlide.prototype.createElement = function() {
		var container = Slide.prototype.createElement.call(this);
		var center = container.querySelector('.slide__center');

		// Create title containing the question we're asking
		var title = document.createElement('h2');
		title.textContent = this.question;
		center.appendChild(title);

		// Create list of options
		var list = document.createElement('ul');
		list.setAttribute('class', 'options');
		
		var self = this; // To avoid binding functions...
		this.options.forEach(function(option, index) {
			// Create li elements containing buttons, bound on click
			// to our handler, which takes the button index as a param
			// so that we know which option was selected
			var optionItem = document.createElement('li');
			var optionItemButton = document.createElement('button');
			optionItemButton.textContent = option;
			optionItemButton.addEventListener(
				'click',
				function() { self.onChooseOption(index); }
			);

			optionItem.appendChild(optionItemButton);
			list.appendChild(optionItem);
		});

		center.appendChild(list);

		return container;
	}

	/** SelectionSlide::onChooseOption(int index) */
	SelectionSlide.prototype.onChooseOption = function(index) {
		Events.onAnswerQuestion(index);
	}

	/** RangeSlide::constructor(min, max) */
	var RangeSlide = function(question, min, max) {
		// Call super constructor
		Slide.call(this, SlideType.RANGE_SLIDE);

		this.question = question;
		this.min = min;
		this.max = max;
	}

	/** RangeSlide extends Slide */
	RangeSlide.prototype = Object.create(Slide.prototype);

	/** RangeSlide::createElement overrides Slide::createElement */
	RangeSlide.prototype.createElement = function() {
		var container = Slide.prototype.createElement.call(this);
		var center = container.querySelector('.slide__center');

		// Create title containing the question we're asking
		var title = document.createElement('h2');
		title.textContent = this.question;
		center.appendChild(title);

		// Create slider/range control
		var rangeContainer = document.createElement('div');
		rangeContainer.setAttribute('class', 'range');

		var rangeOptions = [];

		for (var i = this.min; i <= this.max; i++) {
			var rangeOption = document.createElement('button');
			var self = this; // To avoid nested binds

			rangeOption.addEventListener('click', (function(i) { // scope i so it isn't max when this triggers
				return function() {
					// 'Fill' all the numbers before this one, and this one
					rangeOptions
						.forEach(function(opt) {
							if (opt[0] <= i)
								opt[1].classList.add('active');
							else
								opt[1].classList.remove('active');
						});

					self.onChooseOption(i);
				}
			})(i));

			rangeOption.textContent = i;
			rangeContainer.appendChild(rangeOption);
			rangeOptions.push([i, rangeOption]);
		}

		center.appendChild(rangeContainer);

		return container;
	}

	/** RangeSlide::onChooseOption(int num) */
	RangeSlide.prototype.onChooseOption = function(num) {
		Events.onAnswerQuestion(num);
	}

	/** static switchSlide(Slide slide) */
	var switchSlide = function(slide) {
		var newSlideFragment = document.createDocumentFragment();

		// Create the slide element and set its class so we can do the
		// sliding in animation
		var oldSlideElem = document.querySelector('.slide.current-slide'); // TODO make consts for these
		var slideElem = slide.createElement();
		slideElem.classList.add('sliding-in');

		// Add the element to a fragment (this allows for faster DOM modification)
		// before adding to the body
		newSlideFragment.appendChild(slideElem);
		MAIN_ELEM.appendChild(newSlideFragment);		
		oldSlideElem.classList.add('sliding-out');

		// Set our old slide, so we can go back
		lastSlide = oldSlideElem;

		// Add a timeout so that after the slide has slid in, we can set it to
		// the current slide (and stop any animation etc.)
		setTimeout(function() {
			oldSlideElem.parentNode.removeChild(oldSlideElem);
			slideElem.classList.remove('sliding-in')
			slideElem.classList.add('current-slide');
		}, SLIDE_ANIMATION_TIME);
	};

	return {
		// Classes & enums
		SlideType: SlideType,
		Slide: Slide,
		SelectionSlide: SelectionSlide,
		RangeSlide: RangeSlide,

		// Static methods
		switchSlide: switchSlide
	};

})();

/** namespace Events */
var Events = (function() {
	var firstSlide = null,
			startButton = document.querySelector('[data-action="start"]');

	function onStart() {
		if (firstSlide === null)
			return;
		// new Slides.SelectionSlide('Choose one', [ 'One', 'Two', 'Three' ])
		Slides.switchSlide(firstSlide);
	}

	function onAnswerQuestion(answer) {
		Backend.submitAnswer(answer, function(slide) {
			Slides.switchSlide(slide);
		});
	}

	function setupFirstSlide() {
		Backend.getFirstSlide(function(slide) {
			// Enable the start button and set first slide
			firstSlide = slide;
			startButton.textContent = 'Begin';
		});
	}

	function setupListeners() {
		var actionBtns = Array.prototype.slice.call(
			document.querySelectorAll('[data-action]')
		);

		actionBtns.forEach(function(actionBtn) {
			var action = actionBtn.getAttribute('data-action');
			if (action === 'start')
				actionBtn.addEventListener('click', onStart);
		});
	}

	return {
		setupListeners: setupListeners,
		setupFirstSlide: setupFirstSlide,
		onAnswerQuestion: onAnswerQuestion
	}
})();

Events.setupListeners();
Events.setupFirstSlide();