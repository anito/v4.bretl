/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

(function ($) {
    
    jQuery('.fancybox').each(function (galleryIndex) {

        jQuery('figure img', $(this)).each(function () {
            var src = (s = ($(this).data('src') || $(this).data('lazy-src') || $(this).data('srcset') || $(this).attr('src') )) ? s : '',
                a, c, caption, regex, subst, inner = $(this), outer;
            
            src_big = function (src) {
                
                regex = /(.*)(-\d{1,}x\d{1,})(.)(jpg|jpeg|png|gif)\s?/;
                subst = '$1$3$4';
                
                return src.replace(regex, subst);
                
            }

            caption = $(this).parents('[class*="slide-"]').find('.caption').text();
            
            if( (a = $(this).parent('a')) && a.length ) {
                
                // Links found
                outer = a[0];
            } else {
                // No Links found
                outer = '<a></a>';
                inner.wrap($(outer));
            }
            $(outer).attr( {
                'href': src_big(src),
                'data-fancybox' : 'gallery-' + galleryIndex,
                'data-caption' : caption
            } );


        });
        
        $(this).wrap('<div class="entry-post wrapper"></div>')

    });
    
    $('.fancybox a').fancybox({
        helpers : {
            overlay : {
                css : {
                    'background' : '#f00'
                }
            }
        }
    });

})(jQuery)
