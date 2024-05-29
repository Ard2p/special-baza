@push('footer-scripts')
    @php
    $locale = $page->locale ?? request()->input('_locale');
    @endphp
    <script>
        Admin.WYSIWYG.switchOn('{{$locale}}_content', 'ckeditor', {
            "height": 200,
            "script": true,
            "language": "en",
            "allowedContent": true,
            "extraPlugins": "panelbutton,uploadimage,image2,justify,youtube,uploadfile,colorbutton,colordialog,dialog",
            "0": ",dialogui",
            "1": ",a11yhelp,about,basicstyles,blockquote,clipboard,colorbutton,contextmenu,elementspath,enterkey,entities,filebrowser,floatingspace,font,format,horizontalrule,htmlwriter,image,indentlist,justify,link,list,magicline,maximize,pastefromword,pastetext,removeformat,resize,scayt,showborders,sourcearea,specialchar,stylescombo,tab,table,tableselection,tabletools,toolbar,undo,uploadimage,wsc,wysiwygarea",
            "colorButton_enableAutomatic": true,
            "removeButtons": "Save",
            "removePlugins": "About",
            "uploadUrl": "https:\/\/office.trans-baza.ru\/ckeditor\/upload\/image?_token=hxsMP2sPMnE13euSTuYH5t2qwVQYqXTLYSWl5hri",
            "filebrowserUploadUrl": "https:\/\/office.trans-baza.ru\/ckeditor\/upload\/image?_token=hxsMP2sPMnE13euSTuYH5t2qwVQYqXTLYSWl5hri"
        })
        Admin.WYSIWYG.switchOn('{{$locale}}_form_content', 'ckeditor', {
            "height": 200,
            "script": true,
            "language": "en",
            "allowedContent": true,
            "extraPlugins": "panelbutton,uploadimage,image2,justify,youtube,uploadfile,colorbutton,colordialog,dialog",
            "0": ",dialogui",
            "1": ",a11yhelp,about,basicstyles,blockquote,clipboard,colorbutton,contextmenu,elementspath,enterkey,entities,filebrowser,floatingspace,font,format,horizontalrule,htmlwriter,image,indentlist,justify,link,list,magicline,maximize,pastefromword,pastetext,removeformat,resize,scayt,showborders,sourcearea,specialchar,stylescombo,tab,table,tableselection,tabletools,toolbar,undo,uploadimage,wsc,wysiwygarea",
            "colorButton_enableAutomatic": true,
            "removeButtons": "Save",
            "removePlugins": "About",
            "uploadUrl": "https:\/\/office.trans-baza.ru\/ckeditor\/upload\/image?_token=hxsMP2sPMnE13euSTuYH5t2qwVQYqXTLYSWl5hri",
            "filebrowserUploadUrl": "https:\/\/office.trans-baza.ru\/ckeditor\/upload\/image?_token=hxsMP2sPMnE13euSTuYH5t2qwVQYqXTLYSWl5hri"
        })
    </script>
@endpush