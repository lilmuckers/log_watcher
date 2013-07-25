function LogWatcher()
{
  //set up the handles
  this.handles = jQuery('ul.nav a');
  this.handleContainer = jQuery('ul.nav li');
  this.logs = jQuery('div.container div.span12');
  this.lineNos = jQuery('span.lineno');
  
  //hide all the log containers
  this.logs.hide();
  
  //Attach functions to the handles
  this.handleSetup();
  
  //attach the functions to the line nos
  this.lineNoSetup();
  
  //set up the pulse
  this.setupPulse();
  
  //click the first one
  this.handles.first().click();
}

LogWatcher.prototype.skipToBottom = function(file)
{
  var log = this._getLog(file).find('pre');
  log.scrollTop(log.prop('scrollHeight'));
}

LogWatcher.prototype.handleSetup = function()
{
  this.handles.children('i').hide();
  this.handles.click(this._clickHandle.bind(this));
}

LogWatcher.prototype._clickHandle = function(event)
{
  var target = jQuery(event.delegateTarget);
  
  //remove new notification alert
  target.children('i').hide();
  
  //get the log type
  var logType = target.data('file');
  
  //hide all the logs
  this.logs.hide();
  
  //remove the active status
  this.handleContainer.removeClass('active');
  
  //show the log chunk
  this._getLog(logType).show();
  
  //skip to bottom
  this.skipToBottom(logType);
  
  //set the handle to active
  target.parent('li').addClass('active');
}
LogWatcher.prototype._getLog = function(file)
{
  //show the log chunk
  for(var i = 0; i< this.logs.length; i++){
    var log = this.logs[i];
    if(jQuery(log).data('file') == file){
      return jQuery(log);
    }
  }
}
LogWatcher.prototype._getHandle = function(file)
{
  //show the log chunk
  for(var i = 0; i< this.handles.length; i++){
    var handle = this.handles[i];
    if(jQuery(handle).data('file') == file){
      return jQuery(handle);
    }
  }
}
LogWatcher.prototype.lineNoSetup = function()
{
  this.lineNos.off('click');
  this.lineNos.click(this._toggleMarker.bind(this));
}
LogWatcher.prototype._toggleMarker = function(event)
{
  var target = jQuery(event.delegateTarget);
  target.next().toggleClass('marker');
}
LogWatcher.prototype.setupPulse = function()
{
  this.timer = setInterval(this._pulse.bind(this), 1000);
}
LogWatcher.prototype._pulse = function()
{
  //setup the data
  var data = {
    files: {
    }
  }
  
  //iterate through the logs
  for(var i = 0; i < this.logs.length; i++){
    var log = jQuery(this.logs[i]);
    data.files[log.data('file')] = log.data('latest');
  }
  
  jQuery.ajax('index.php', {
    data: data,
    type: 'POST',
    success: this._handleSuccess.bind(this)
  });
}
LogWatcher.prototype._handleSuccess = function(data, status, xhr){
  for(var file in data){
    var log = this._getLog(file);
    var startLine = parseInt(log.find('span').last().prev().html()) + 1;
    
    //start line
    if(!startLine) startLine = 1;
    
    for(var date in data[file]){
      var message = data[file][date];
      date = date.split('___')[0];
      log.data('latest', date);
      
      jQuery("<span class=\"lineno\">"+startLine.toString().pad(4, ' ', 'left')+"</span><span class=\"message\">"+message+"</span><br>").appendTo(log.find('code'));
      console.log(log.data('latest'), startLine, message);
      startLine++;
      
      if(!this._getHandle(file).hasClass('active')){
        this._getHandle(file).children('i').show();
      }
      this.skipToBottom(file);
    }
    
    //re-register the lineNoSetup
    this.lineNos = jQuery('span.lineno');
    this.lineNoSetup();
  }
}


var logWatcher = new LogWatcher();


// arguments are "length", "character", and "direction"
String.prototype.pad = String.prototype.pad || function(len, chr, dir) {
    var str = this;
    len = (typeof len == 'number') ? len : 0;
    chr = (typeof chr == 'string') ? chr : ' ';
    dir = (/left|right|both/i).test(dir) ? dir : 'right';
    var repeat = function(c, l) { // inner "character" and "length"
        var repeat = '';
        while (repeat.length < l) {
            repeat += c;
        }
        return repeat.substr(0, l);
    }
    var diff = len - str.length;
    if (diff > 0) {
        switch (dir) {
            case 'left':
                str = '' + repeat(chr, diff) + str;
                break;
            case 'both':
                var half = repeat(chr, Math.ceil(diff / 2));
                str = (half + str + half).substr(1, len);
                break;
            default: // and "right"
                str = '' + str + repeat(chr, diff);
        }
    }
    return str;
};