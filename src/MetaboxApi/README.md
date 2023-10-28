# Attributes

| Name                | Type       | Default  | Description                                            |
|---------------------|------------|----------|--------------------------------------------------------|
| `type`              | `string`   | `'text'` | Type of input field. See available input types.        |
| `id`                | `string`   | `''`     | The ID of the field. Must be unique.                   |
| `label`             | `string`   | `''`     | Field label.                                           |
| `description`       | `string`   | `''`     | Field description.                                     |
| `default`           | `string`   | `''`     | Field default value.                                   |
| `priority`          | `number`   | `200`    | Field default value.                                   |
| `sanitize_callback` | `callable` | `''`     | The function to be used to sanitize value.             |
| `label_class`       | `string`   | `''`     | CSS class name to be use on field label.               |
| `field_class`       | `string`   | `''`     | CSS class name to be use on input field.               |
| `choices`           | `array`    | `[]`     | The list of options to be use on select, radio fields. |
| `input_attributes`  | `array`    | `[]`     | Additional input attributes to be passed on field.     |

# Choices Array Attributes

| Name       | Type      | Default  | Description                |
|------------|-----------|----------|----------------------------|
| `label`    | `string`  | `'text'` | The label of the option.   |
| `value`    | `string`  | `'text'` | The value of the option.   |
| `disabled` | `boolean` | `false`  | Should disable the option. |

# Input Types

* `text`
* `textarea`
* `checkbox`
* `select`
* `radio`

<details>
<summary>CSS for tab style</summary>

```css
.shapla-tabs-wrapper {
    display: block;
    overflow: hidden;
    background: #fff;
    margin: -6px -12px -12px;
    padding: 0;
}

.ui-tabs.shapla-tabs {
    border: none;
    border-radius: 0;
    margin: 0;
    padding: 0;
    background: none;
    font-size: 1em;
}

.ui-tabs.shapla-tabs ul.shapla-tabs-list {
    margin: 0;
    width: 20%;
    float: left;
    line-height: 1em;
    padding: 0 0 10px;
    position: relative;
    box-sizing: border-box;
    border: none;
    border-right: 1px solid #eee;
    border-radius: 0;
    background: #fafafa;
}

.ui-tabs.shapla-tabs ul.shapla-tabs-list::after {
    content: "";
    display: block;
    width: 100%;
    height: 9999em;
    position: absolute;
    bottom: -9999em;
    left: 0;
    background-color: #fafafa;
    border-right: 1px solid #eee;
}

.ui-tabs.shapla-tabs ul.shapla-tabs-list li {
    margin: 0;
    padding: 0;
    display: block;
    position: relative;
    width: 100%;
    background: none;
    border-radius: 0;
    border: none;
}

.ui-tabs.shapla-tabs ul.shapla-tabs-list li a {
    margin: 0;
    padding: 10px !important;
    display: block;
    box-shadow: none;
    text-decoration: none;
    line-height: 20px !important;
    border-bottom: 1px solid #eee;
    width: 100%;
    box-sizing: border-box;
}

.ui-tabs.shapla-tabs ul.shapla-tabs-list li a span {
    margin-left: 0.618em;
    margin-right: 0.618em;
}

.ui-tabs.shapla-tabs ul.shapla-tabs-list li a::before {
    font-family: "Dashicons";
    speak: none;
    font-weight: normal;
    font-variant: normal;
    text-transform: none;
    line-height: 1;
    -webkit-font-smoothing: antialiased;
    content: "";
    text-decoration: none;
}

.ui-tabs.shapla-tabs ul.shapla-tabs-list li.ui-tabs-active {
    border-radius: 0;
    border: none;
    margin: 0;
    padding: 0;
}

.ui-tabs.shapla-tabs ul.shapla-tabs-list li.ui-tabs-active a {
    color: #555;
    position: relative;
    background-color: #eee;
}

.ui-tabs.shapla-tabs ul.shapla-tabs-list li.active a {
    color: #555;
    position: relative;
    background-color: #eee;
}

.ui-tabs.shapla-tabs .shapla_options_panel {
    float: left;
    width: 80%;
    min-height: 175px;
    box-sizing: border-box;
    padding: 0;
    color: #555;
}

@media only screen and (max-width: 900px) {
    .ui-tabs.shapla-tabs ul.shapla-tabs-list {
        width: 10%;
    }

    .ui-tabs.shapla-tabs ul.shapla-tabs-list li a {
        position: relative;
        text-indent: -999px;
        padding: 10px;
    }

    .ui-tabs.shapla-tabs ul.shapla-tabs-list li a::before {
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
        text-indent: 0;
        text-align: center;
        line-height: 40px;
        width: 100%;
        height: 40px;
    }
}

```

</details>

<details>
<summary>SCSS for meta box style</summary>

```scss
.sp {
  &-input-group {
    margin-bottom: 10px;

    &:after {
      content: "";
      display: table;
      clear: both
    }
  }

  &-input-label {
    label {
      font-weight: 600;
      margin-right: 30px;
    }
  }

  &-input-field {
  }

  &-input-label,
  &-input-field {
    float: left;
    width: 100%;
  }

  &-input-desc {
    color: #999;
    font-size: 0.9em;
    line-height: 1.3em;
    margin: 10px 30px 10px 0;
  }

  &-input-text,
  &-input-textarea {
    width: 100%;
    padding: 8px 8px 8px 16px;
  }

  &-input-text {
    height: 35px !important;

    option {
      padding: 8px 4px;
    }
  }

  &-input-textarea {
  }
}

.spacing-text {
  width: 62px;
}

@media only screen and (min-width: 600px) {
  .sp {
    &-input-label {
      width: 40%;
    }

    &-input-field {
      width: 60%;
    }
  }
}

@media only screen and (min-width: 783px) {
  .sp-input-label {
    width: 30%;
  }
  .sp-input-field {
    width: 70%;
  }
  .sp-input-text,
  .sp-input-textarea:not(cols) {
    width: 25em;
  }
}

@media only screen and (min-width: 851px) {
  .sp-input-label,
  .sp-input-field {
    width: 100%;
  }
  .sp-input-label {
    margin-bottom: .5rem;
  }
}

@media only screen and (min-width: 1200px) {
  .sp-input-label {
    width: 40%;
  }
  .sp-input-field {
    width: 60%;
  }
}

@media only screen and (min-width: 1600px) {
  .sp-input-label {
    width: 30%;
  }
  .sp-input-field {
    width: 70%;
  }
}
```
</details>

<details>
<summary>CSS style for radio button</summary>

```scss
.radio-button {
  display: flex;
  flex-wrap: wrap;

  .radio-button-label {
    background: rgba(0, 0, 0, .05);
    border-right: 1px solid rgba(0, 0, 0, .2);
    color: #555;
    margin: 0;
    padding: 0.5em 1em;
    font-size: 14px;
    flex-grow: 0;
    text-align: center;

    &:last-child {
      border-right: none;
    }
  }

  .radio-button-input {
    display: none;

    &:checked {
      + .radio-button-label {
        background-color: #3498DB;
        color: #fff;
      }
    }
  }
}
```
</details>

<details>
<summary>SCSS for checkbox switch</summary>

```scss
.switch-container {
  position: relative;

  label {
    display: flex;
    flex-wrap: wrap;

    .customize-control-title {
      width: calc(100% - 55px);
    }

    .description {
      order: 99;
    }
  }

  .switch {
    border: 1px solid #b4b9be;
    display: inline-block;
    width: 35px;
    height: 12px;
    border-radius: 8px;
    background: #b4b9be;
    vertical-align: middle;
    position: relative;
    top: 4px;
    cursor: pointer;
    user-select: none;
    transition: background 350ms ease;
    text-indent: -999999px;

    &:after,
    &:before {
      content: "";
      display: block;
      width: 20px;
      height: 20px;
      border-radius: 50%;
      position: absolute;
      top: 50%;
      left: -3px;
      transition: all 350ms cubic-bezier(0, 0.95, 0.38, 0.98), background 150ms ease;
    }

    &:before {
      background: rgba(0, 0, 0, 0.2);
      transform: translate3d(0, -50%, 0) scale(0);
    }

    &:after {
      background: #999;
      border: 1px solid rgba(0, 0, 0, 0.1);
      transform: translate3d(0, -50%, 0);
    }

    &:active:before {
      transform: translate3d(0, -50%, 0) scale(3);
    }
  }

  input[type="checkbox"]:checked::before {
    display: none;
  }

  input:checked + .switch:before {
    background: rgba(0, 115, 170, 0.075);
    transform: translate3d(100%, -50%, 0) scale(1);
  }

  input:checked + .switch:after {
    background: #0073aa;
    transform: translate3d(100%, -50%, 0);
  }

  input:checked + .switch:active:before {
    background: rgba(0, 115, 170, 0.075);
    transform: translate3d(100%, -50%, 0) scale(3);
  }
}
```
</details>

<details>
<summary>Media Frame Select JavaScript code</summary>

```js
const defaultArgs      = {
    title: 'Featured image',
    buttonText: 'Set featured image',
    inputTargetName: '',
    previewTarget: '',
    container: '.field-media-frame-select',
    type: 'image',
    multiple: false,
};
const listItemTemplate = (src) => {
    return '<li><img src=\'' + src + '\' width=\'150\' height=\'150\' class=\'attachment-150x150 size-150x150\' loading=\'lazy\' /></li>';
};
jQuery( '[data-media-frame="select"]' ).on(
    'click',
    function(event) {
        event.preventDefault();
        const dataset = Object.assign( defaultArgs, event.target.dataset );
        const frame   = new wp.media.view.MediaFrame.Select(
            {
                title: dataset.title,
                multiple: dataset.multiple,
                library: {
                    order: 'ASC',
                    orderby: 'title',
                    type: dataset.type,
                    search: null,
                    uploadedTo: null,
                },
                button: {
                    text: dataset.buttonText,
                },
            },
        );

        frame.on(
            'select',
            function() {
                let selectionCollection = frame.state().get( 'selection' ),
                    ids                 = [],
                    html                = '';
                selectionCollection.forEach(
                    function(attachment) {
                        ids.push( attachment.id );
                        if ('video' === attachment.attributes.type) {
                            let src = attachment.attributes.thumb.src || attachment.attributes.image.src;
                            html   += listItemTemplate( src );
                        } else if ('image' === attachment.attributes.type) {
                            let src = attachment.attributes.sizes.thumbnail.url || attachment.attributes.sizes.full.url;
                            html   += listItemTemplate( src );
                        }
                    },
                );

                const container = jQuery( event.target ).closest( dataset.container );
                container.find( `[name = "${dataset.inputTargetName}"]` ).val( ids.toString() );
                container.find( dataset.previewTarget ).html( html );
            },
        );

        // Open the modal.
        frame.open();
    },
);
jQuery( '[data-media-frame-reset="select"]' )
    .on(
        'click',
        function(event) {
            event.preventDefault();
            let dataset = Object.assign( defaultArgs, event.target.dataset );
            jQuery( `[name = "${dataset.inputTargetName}"]` ).val( '' );
            jQuery( dataset.previewTarget ).html( '' );
        },
    );

```

</details>

<details>
<summary>Media Frame Gallery JavaScript code</summary>

```js
let frame,
	_this = jQuery('#carousel_slider_gallery_btn'),
	images = _this.data('ids'),
	selection = loadImages(images);

const updateDom = (ids_string, gallery_html) => {
	jQuery('#_carousel_slider_images_ids').val(ids_string);
	jQuery('.carousel_slider_gallery_list').html(gallery_html);
}

_this.on('click', function (e) {
	e.preventDefault();
	let options = {
		title: _this.data('create'),
		state: 'gallery-edit',
		frame: 'post',
		selection: selection
	};

	if (frame || selection) {
		options['title'] = _this.data('edit');
	}

	frame = wp.media(options).open();

	// Tweak Views
	frame.menu.get('view').unset('cancel');
	frame.menu.get('view').unset('separateCancel');
	frame.menu.get('view').get('gallery-edit').el.innerHTML = _this.data('edit');
	frame.content.get('view').sidebar.unset('gallery'); // Hide Gallery Settings in sidebar

	// when editing a gallery
	overrideGalleryInsert()
	frame.on('toolbar:render:gallery-edit', function () {
		overrideGalleryInsert()
	});

	frame.on('content:render:browse', function (browser) {
		if (!browser) return;
		// Hide Gallery Settings in sidebar
		browser.sidebar.on('ready', function () {
			browser.sidebar.unset('gallery');
		});
		// Hide filter/search as they don't work
		browser.toolbar.on('ready', function () {
			if (browser.toolbar.controller._state === 'gallery-library') {
				browser.toolbar.$el.hide();
			}
		});
	});

	// All images removed
	frame.state().get('library').on('remove', function () {
		let models = frame.state().get('library');
		if (models.length === 0) {
			selection = false;
			updateDom('', '');
		}
	});
});

const onClickModalSaveButton = () => {
	let models = frame.state().get('library'),
		ids = [],
		html = '';

	models.each(function (attachment) {
		ids.push(attachment.id);
		let src = attachment.attributes.sizes.thumbnail || attachment.attributes.sizes.full;
		html += `<li><img src="${src.url}" width="50" height="50" class="attachment-50x50 size-50x50" loading="lazy"></li>`;
	});

	selection = loadImages(ids.toString());
	frame.close();
	updateDom(ids.toString(), html);
}

function overrideGalleryInsert() {
	frame.toolbar.get('view').set({
		insert: {
			style: 'primary',
			text: _this.data('save'),
			click: () => onClickModalSaveButton()
		}
	});
}

function loadImages(images) {
	if (!images) {
		return false;
	}

	if (typeof images !== 'string') {
		images = images.toString();
	}

	let shortcode = new wp.shortcode({
		tag: 'gallery',
		attrs: {ids: images},
		type: 'single'
	});

	let attachments = wp.media.gallery.attachments(shortcode);

	let selection = new wp.media.model.Selection(attachments.models, {
		props: attachments.props.toJSON(),
		multiple: true
	});

	selection.gallery = attachments.gallery;

	selection.more().done(function () {
		// Break ties with the query.
		selection.props.set({query: false});
		selection.unmirror();
		selection.props.unset('orderby');
	});

	return selection;
}
```
</details>