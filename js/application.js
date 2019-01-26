/**
 * namespace Slides
 *
 * This namespace contains classes and static methods to handle
 * 'slides'. Slides are named after the same concept in slideshows
 * and presentations, particularly due to their 'sliding' animation.
 * Slides fill the entire screen and may be hot-swapped out.
 */
var Slides = (function() {

	/** enum SlideType */
	var SlideType = {
		QUESTION_SLIDE: 1
	};

	/** class Slide: constructor(SlideType type) */
	var Slide = function(type) {
		this.type = type;
	};

	/** static switchSlide(Slide slide) */
	var switchSlide = function(slide) {
		switch (slide.type) {
			case SlideType.QUESTION_SLIDE:
				console.log('Switching to a question slide');
				break;
			default:
				console.log('Error: unrecognized slide type');
		}
	};

	return {
		// Classes & enums
		SlideType: SlideType,
		Slide: Slide,

		// Static methods
		switchSlide: switchSlide
	};

})();

Slides.switchSlide(new Slides.Slide(Slides.SlideType.QUESTION_SLIDE));