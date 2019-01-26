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
		SELECTION_SLIDE: 1
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
		
		this.options.forEach(function(option) {
			var optionItem = document.createElement('li');
			var optionItemButton = document.createElement('button');
			optionItemButton.textContent = option;
			optionItem.appendChild(optionItemButton);
			list.appendChild(optionItem);
		});

		center.appendChild(list);

		return container;
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

		// Static methods
		switchSlide: switchSlide
	};

})();

var Events = (function() {
	function onStart() {
		Slides.switchSlide(new Slides.SelectionSlide('Choose one', [ 'One', 'Two', 'Three' ]));
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
		setupListeners: setupListeners
	}
})();

Events.setupListeners();