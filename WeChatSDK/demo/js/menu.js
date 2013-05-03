$(function(){
  // search params
  /*
  var _search = {};
  location.search.replace(
    /[\?&]([^\?&]+)=([^\?&]+)/g, function(match, key, val, index, str){
      _search[decodeURIComponent(key)] = decodeURIComponent(val);
    } 
  );
  /**/
  // json format
  var formatJSON = function(json){
    var indent = 0;
    return json.replace(
      /([^\{\[\]\},]*)([\{\[\]\},])/g, 
      function(match, content, end, index, json){
        var padding = indent;
        switch(true){
          case /[\[\{]/.test(end):
            indent++;
            break;
          case /[\]\}]/.test(end):
            padding = --indent;
            break;
          //case /,/.test(end):default:
        }
        return /* '[' + padding + ']' + */ formatJSON.repeat('  ', padding) 
          + match + '\n';
      }
    );
  }
  formatJSON.repeat = function(str, time){
    var result = '';
    while(time-->0){
      result += str;
    }
    return result;
  }; 

  var 
    _api           = './bizmenu.php',
    _$jsontextarea = $('#menu-json  '),
    _$btns         = $('.pannel-row .btn'),
    _disableBtns   = function(){ _$btns.attr('disabled', 'disabled'); },
    _enableBtns    = function(){ _$btns.removeAttr('disabled');};

  $('#get-menu').click(function(){
    if(this.disabled) return;
    _disableBtns();
    artDialog.loading();
    $.ajax(_api, {
      type : 'post',
      dataType : 'text',
      data : {
        action    : 'get'
        // , appid     : _search.appid
        // , appsecret : _search.appsecret
      },
      success : function(data){
                  _$jsontextarea.val( formatJSON(data)); 
                },
      failure : function(){
                  artDialog.tips('Failure');
                },
      complete : function(data){
                   _enableBtns(); 
                   artDialog.loading.close();
                 }
    });
  });
  $('#set-menu').click(function(){
    var json = _$jsontextarea.val();
    if(this.disabled || !json) return;
    _disableBtns();
    artDialog.loading();

    $.ajax(_api, {
      type : 'post',
      dataType : 'json',
      data : {
        action    : 'set'
        // ,appid     : _search.appid
        // ,appsecret : _search.appsecret
        ,json      : json
      },
      success : function(data){
                  if(!data.ok){
                    artDialog.tips('Failure BKZ : ' + data.msg, 1000);
                    return;
                  }
                  artDialog.tips('Successfully, refresh...', 1000);
                  setTimeout(function(){
                    $('#get-menu').trigger('click');
                  },1000);
                },
      failure : function(){
                  artDialog.tips('Failure');
                },
      complete : function(data){
                   _enableBtns(); 
                   artDialog.loading.close();
                 }
    });
  });
  $('#del-menu').click(function(){
    if(this.disabled) return;
    _disableBtns();
    artDialog.loading();

    $.ajax(_api, {
      type : 'post',
      dataType : 'json',
      data : {
        action    : 'del'
        // ,appid     : _search.appid
        // ,appsecret : _search.appsecret
      },
      success : function(data){
                  if(!data.ok){
                    artDialog.tips('Failure BKZ : ' + data.msg, 1000);
                    return;
                  }
                  artDialog.tips('Success');
                },
      failure : function(){
                  artDialog.tips('Failure');
                },
      complete : function(data){
                   _enableBtns(); 
                   artDialog.loading.close();
                 }
    });
  });
});

(function($, artDialog){
  var aLoading;
  artDialog.loading = function(){
    if(aLoading) return aLoading.content();  
    return (aLoading = artDialog({
      id     : 'Tips',
      title  : false,
      cancel : false,
      fixed  : true,
      lock   : false
    }).content());
  };
  artDialog.loading.close = function(){
    aLoading && aLoading.close();
    aLoading = null;
  };
  artDialog.tips = function(str, time){
    artDialog({
      title  : false,
      cancel : false,
      fixed  : true,
      lock   : false
    }).content('' + str).time(time || 1500);
  }
})($, artDialog);

