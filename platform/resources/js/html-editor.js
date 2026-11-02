// Alpine component of Platform\Filament\Components\HtmlEditor: Jodit (MIT, platform/resources/dist/jodit)
// on the field's HTML — visual editing, the HTML source, pasted HTML kept as HTML, Media Library button.
// Jodit is loaded once, the first time an editor appears.

let loading = null

const load = (script, style) =>
    (loading ??= new Promise((resolve, reject) => {
        if (window.Jodit) {
            return resolve(window.Jodit)
        }

        const css = document.createElement('link')
        css.rel = 'stylesheet'
        css.href = style
        document.head.append(css)

        const js = document.createElement('script')
        js.src = script
        js.onload = () => resolve(window.Jodit)
        js.onerror = reject
        document.head.append(js)
    }))

export default function htmlEditorFormComponent({ state, key, script, style, contentCss, height, isDisabled, labels }) {
    return {
        state,

        editor: null,

        async init() {
            const Jodit = await load(script, style)

            this.editor = Jodit.make(this.$refs.area, {
                height,
                readonly: isDisabled,
                theme: document.documentElement.classList.contains('dark') ? 'dark' : 'default',
                toolbarAdaptive: false,
                toolbarSticky: false,
                // the page text as the website shows it: inside a frame with the theme's content CSS
                iframe: true,
                iframeCSSLinks: contentCss,
                iframeStyle: 'body{padding:1rem;background:#fff;color:#212529}',
                // pasted HTML stays HTML (classes, tables, cards)
                askBeforePasteHTML: false,
                askBeforePasteFromWord: false,
                defaultActionOnPaste: 'insert_as_html',
                cleanHTML: { fillEmptyParagraph: false },
                // pictures and files come from the Media Library, never inline (FR-GEN-60)
                uploader: { insertImageAsBase64URI: false },
                showCharsCounter: false,
                showWordsCounter: false,
                showXPathInStatusbar: false,
                buttons: [
                    'source', '|',
                    'bold', 'italic', 'underline', 'strikethrough', 'superscript', 'subscript', '|',
                    'paragraph', 'fontsize', 'brush', '|',
                    'ul', 'ol', 'outdent', 'indent', 'align', '|',
                    'link',
                    { name: 'media', icon: 'image', tooltip: labels.media, exec: () => this.openMedia() },
                    'table', 'hr', '|',
                    'eraser', 'copyformat', '|',
                    'undo', 'redo', '|',
                    'fullsize',
                ],
            })

            this.editor.value = this.state ?? ''
            this.editor.events.on('change', (value) => {
                this.state = value
            })
            this.$watch('state', (value) => {
                if ((value ?? '') !== this.editor.value) {
                    this.editor.value = value ?? ''
                }
            })
        },

        openMedia() {
            this.editor.s.save()
            this.$wire.mountAction('insertMedia', {}, { schemaComponent: key })
        },

        // the chosen files' HTML, from the insertMedia action
        insert(detail) {
            if (detail.key !== key || !this.editor) {
                return
            }

            this.editor.s.restore()
            this.editor.s.insertHTML(detail.html)
            this.state = this.editor.value
        },

        destroy() {
            this.editor?.destruct()
        },
    }
}
