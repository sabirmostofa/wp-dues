/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

jQuery(document).ready(function($){
    
    
    $('#earlybird_date').datepicker();
    
        $('.widefat img').bind('click',function(evt){
			alert('clicked');
        evt.preventDefault();
        var id =$(this).attr('id');
        
        var self = $(this);
        
            $.ajax({
            type :  "post",
            url : ajaxurl,
            timeout : 5000,
            data : {
                'action' : 'membership_remove',
                'id' : id		  
            },			
            success :  function(data){         

                 self.parent().parent().parent().hide('slow');   

            }
        })	//end of ajax	
        
        })
})
