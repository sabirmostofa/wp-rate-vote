jQuery(document).ready(function($){
    
    var grades_val= new Array('Awesome!', 'Pretty Good', 'Just Ok', 'Pretty Lame', 'Worthless' );
       var common ={
           updating: false,
           gray_all: function(){
            $('.rate-grade-image a img').each(function(){
                var src =  $(this).attr('src');
                var src_color =  src.replace('color','gray');
                if( $(this).attr('src').match('color'))
                    $(this).attr('src', src_color);
                $(this).attr('id',null);
            });

           },
           color_src: function(grade){
                $('.rate-grade-image a img').each(function(){
                var src =  $(this).attr('src');
                var selVal =$(this).attr('class').match(/\d/);
                if(selVal[0] == grade){
                var src_color =  src.replace('gray','color');               
                    $(this).attr('src', src_color);
                    $(this).attr('id','user-grade');
                }
                });
               
           }
       }
       
       //initialize variable
            $('.rate-grade-image a img').each(function(){
            if( $(this).attr('src').match('color'))                 
                    common.updating = true;              
            
        })
   
    
    $('.rate-grade-image a img').mouseover(function(e){
        var elem = $(this);
        e.stopPropagation();
        var src = elem.attr('src');
//        var image = src.match(/\/[^\/]*\.png/);
//       alert(src.replace(/\/[^\/]*\.png/,''));
       var new_image= src.replace('gray','color');
       elem.attr('src', new_image);
       var selVal =$(this).attr('class').match(/\d/);
       $('.grade-text-value').html(grades_val[selVal[0]]);
        
    });
    $('.rate-grade-image a img').mouseout(function(e){
        var elem = $(this);
        e.stopPropagation();
        var src = elem.attr('src');
       var new_image= src.replace('color','gray');

       if($(this).attr('id') != 'user-grade')
            elem.attr('src', new_image);
        $('.grade-text-value').html(null);
        
    });

    
    $('.rate-grade-image a img').click(function(e){
        e.preventDefault();
        var action = 'submit-wpvote';
       var hovered_img = $(this).attr('src'); 
        
        var selVal =$(this).attr('class').match(/\d/);
        var to_update = false;
        $('.rate-grade-image a img').each(function(){
            if( $(this).attr('src').match('color')) 
                if($(this).attr('src') != hovered_img)
                    to_update = true;
                
            
        })
       
//        alert(wpvrSettings.post_id );
      var ans = true;
      if(common.updating)
           ans = confirm("Are you sure you want to update the grade?");
       
        if(ans == false) return;
        $.ajax({
            type :  "post",
            url : wpvrSettings.ajaxurl,
            timeout : 5000,
            dataType: 'json',
            data : {
                'action' : action,
                'grade-value': selVal[0],
                 'post_id':  wpvrSettings.post_id  
            },
            success :  function(data){
//                $('#colophon').html(data);          
                
                switch(data.action){
                    case 'added':
                      common.gray_all();
                      common.color_src(data.grade);
                      common.updating = true;
                     break;
                    case 'updated':
                        common.gray_all();
                        common.color_src(data.grade);
                     break;

                }
//                   if(data == 'voted' )alert('You have already voted for this post');
//                   if(data == 'nv')alert('Your Rating has been saved for this post');
//                    if(data == 'updated')alert('Your Rating has been Updated for this post');
                   // window.location.href=window.location.href;
                    
                    }//end ajax success
            })//end ajax
        })//end click logic
})



