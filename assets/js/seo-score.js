/**
 * Morn SEO Meta Kit 编辑页实时评分。
 *
 * 纯原生实现，无外部依赖。
 */
( function () {
	'use strict';

	/**
	 * 统计字符长度（按 Unicode 码点计）。
	 *
	 * @param {string} value 字符串。
	 * @return {number} 长度。
	 */
	function charLength( value ) {
		return Array.from( value || '' ).length;
	}

	/**
	 * 取两个值之间 0~100 的比例进度。
	 *
	 * @param {number} length 当前长度。
	 * @param {number} min   建议最小值。
	 * @param {number} max   建议最大值。
	 * @return {number} 0~100。
	 */
	function progress( length, min, max ) {
		if ( length < min ) {
			return Math.round( ( length / min ) * 60 );
		}

		if ( length > max ) {
			var over = Math.min( ( length - max ) / max, 1 );
			return Math.max( 20, Math.round( 100 - over * 80 ) );
		}

		return 100;
	}

	/**
	 * 设置评分条状态。
	 *
	 * @param {HTMLElement} bar    评分条元素。
	 * @param {number}      ratio  0~100 比例。
	 * @return {void}
	 */
	function paint( bar, ratio ) {
		if ( ! bar ) {
			return;
		}

		bar.style.width = ratio + '%';
		bar.classList.remove( 'is-warn', 'is-bad' );

		if ( ratio >= 80 ) {
			return;
		}

		if ( ratio >= 45 ) {
			bar.classList.add( 'is-warn' );
		} else {
			bar.classList.add( 'is-bad' );
		}
	}

	/**
	 * 更新字数计数器。
	 *
	 * @param {HTMLElement} input 输入框。
	 * @return {void}
	 */
	function updateCounter( input ) {
		var counter = document.querySelector( '.morn-counter[data-target="' + input.id + '"]' );

		if ( ! counter ) {
			return;
		}

		var min = parseInt( counter.getAttribute( 'data-min' ), 10 ) || 10;
		var max = parseInt( counter.getAttribute( 'data-max' ), 10 ) || 60;
		var length = charLength( input.value );

		counter.textContent = String( length );
		counter.classList.remove( 'is-ok', 'is-warn' );

		if ( length >= min && length <= max ) {
			counter.classList.add( 'is-ok' );
		} else {
			counter.classList.add( 'is-warn' );
		}
	}

	/**
	 * 主更新函数。
	 *
	 * @return {void}
	 */
	function update() {
		var titleInput = document.getElementById( 'morn-seo-title' );
		var descInput = document.getElementById( 'morn-seo-desc' );
		var keywordInput = document.getElementById( 'morn-seo-keywords' );

		if ( ! titleInput || ! descInput ) {
			return;
		}

		updateCounter( titleInput );
		updateCounter( descInput );

		var titleBar = document.querySelector( '[data-bar="title"]' );
		var descBar = document.querySelector( '[data-bar="desc"]' );

		paint( titleBar, progress( charLength( titleInput.value ), 10, 60 ) );
		paint( descBar, progress( charLength( descInput.value ), 50, 155 ) );

		var hint = document.querySelector( '[data-hint="keyword"]' );

		if ( hint && keywordInput ) {
			var keyword = keywordInput.value.trim();
			var strings = window.mornSeoMetaKitAdmin || {};

			if ( keyword === '' ) {
				hint.textContent = strings.noKeyword || '未填写焦点关键词';
				return;
			}

			var first = keyword.split( ',' )[ 0 ].trim();
			var inTitle = titleInput.value.toLowerCase().indexOf( first.toLowerCase() ) !== -1;

			hint.textContent = inTitle
				? ( strings.keywordOk || '关键词已出现在标题中' )
				: ( strings.keywordMissing || '关键词未出现在标题中' );
		}
	}

	function init() {
		var fields = document.querySelectorAll( '#morn-seo-title, #morn-seo-desc, #morn-seo-keywords' );

		Array.prototype.forEach.call( fields, function ( field ) {
			field.addEventListener( 'input', update );
			field.addEventListener( 'change', update );
		} );

		update();
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
