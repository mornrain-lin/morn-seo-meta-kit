/**
 * Morn SEO Meta Kit 后台脚本：媒体库选择。
 *
 * 依赖 WordPress 自带的 wp.media，不引入任何外部脚本。
 */
( function () {
	'use strict';

	var strings = window.mornSeoMetaKitAdmin || {};

	/**
	 * 打开媒体库选择器。
	 *
	 * @param {HTMLElement} input 隐藏的附件 ID 输入框。
	 * @return {void}
	 */
	function openMedia( input ) {
		if ( ! window.wp || ! window.wp.media ) {
			return;
		}

		var frame = window.wp.media( {
			title: strings.selectImage || '选择图片',
			button: { text: strings.useImage || '使用此图片' },
			library: { type: 'image' },
			multiple: false
		} );

		frame.on( 'select', function () {
			var attachment = frame.state().get( 'selection' ).first().toJSON();
			var preview = input.parentNode.querySelector( '.morn-image-preview' );

			input.value = attachment.id;

			if ( preview ) {
				var url = attachment.url;

				if ( attachment.sizes && attachment.sizes.medium ) {
					url = attachment.sizes.medium.url;
				}

				preview.innerHTML = '';

				var img = document.createElement( 'img' );
				img.src = url;
				img.alt = '';
				img.style.maxWidth = '160px';
				img.style.height = 'auto';
				preview.appendChild( img );
			}
		} );

		frame.open();
	}

	document.addEventListener( 'click', function ( event ) {
		var target = event.target;

		if ( ! target || typeof target.getAttribute !== 'function' ) {
			return;
		}

		var selectId = target.getAttribute( 'data-morn-image-select' );

		if ( selectId ) {
			event.preventDefault();
			var input = document.getElementById( selectId );

			if ( input ) {
				openMedia( input );
			}
			return;
		}

		var clearId = target.getAttribute( 'data-morn-image-clear' );

		if ( clearId ) {
			event.preventDefault();
			var field = document.getElementById( clearId );
			var box = field ? field.parentNode.querySelector( '.morn-image-preview' ) : null;

			if ( field ) {
				field.value = '';
			}

			if ( box ) {
				box.innerHTML = '';
				var span = document.createElement( 'span' );
				span.className = 'morn-image-empty';
				span.textContent = '未选择图片';
				box.appendChild( span );
			}
		}
	} );
}() );
