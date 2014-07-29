<!DOCTYPE HTML>
<html>
  <head>
  <script type="text/javascript">
  document.addEventListener("DOMContentLoaded", init, false);
  function init(){
    var $                                             = function(sel){
        	var res 				                            = document.querySelectorAll(sel);
        	return Array.prototype.slice.call(res);
    	  },
        $el                                           = $('*[validate]');


    function Validate(el, format){
      this.init(el, format);
    }
    
    Validate.prototype.init                          = function(el, format){
      this.$el                                        = el;
      this.message                                    = el.getAttribute('validatemessage');
      this.type                                       = format;
      el.onchange                                     = this.onchange.bind(this);
    };
    
    Validate.prototype.onchange                      = function(event){
      this.value                                      = this.$el.value;
      if(!this.reg.test(this.value)){
        alert(this.message);
      }
    }
  
    function extend(parent, child){
      var prot                                        = function(){};
      prot.prototype                                  = parent.prototype;
      child.prototype                                 = new prot();
      child.prototype.ancestor                        = parent.prototype;
    }
  
    function ValidateDigit(el, format){
      this.ancestor.constructor.call(this, el, format);
    }
  
    function ValidateLength(el, format){
      this.ancestor.constructor.call(this, el, format);
    }

    function ValidateEmail(el){
      this.ancestor.constructor.call(this, el, format);
    }

    extend(Validate, ValidateDigit);
    extend(Validate, ValidateLength);
    extend(Validate, ValidateEmail);

    ValidateDigit.prototype.onchange                  = function(event){
      this.ancestor.onchange.call(this, event);
    };

    ValidateDigit.prototype.init                      = function(el, format){
      this.ancestor.init.call(this, el, format);
      this.reg                                        = /^\d+$/;
    };

    ValidateLength.prototype.onchange                 = function(event){
      this.ancestor.onchange.call(this, event);
    };

    ValidateLength.prototype.init                     = function(el, format){
      this.ancestor.init.call(this, el, format);
      var data                                        = this.type.split('-');
      this.reg                                        = new RegExp('^.{0,' + data[2] + '}$');
    };

    ValidateEmail.prototype.onchange                  = function(event){
      this.ancestor.onchange.call(this, event);
    };

    ValidateEmail.prototype.init                      = function(el, format){
      this.ancestor.init.call(this, el, format);
      this.reg                                        = /^[^@]+@.+?\.[a-z]{2,}$/i;
    };

    function Validator(el){
      switch(format = el.getAttribute('validate')){
        case 'digits':
        new ValidateDigit(el, format);
        break;
        case 'email':
        new ValidateEmail(el, format);
        break;
        default:
        new ValidateLength(el, format);
      }
    }

    $el.map(function(el){
      new Validator(el);
    });
    
  };
  </script>
  </head>
  <body>
  <div>
  <input id="first" type="text" validate='digits' validatemessage='Digits only'>
  </div>
  <div>
  <input id="second" type="text" validate='email' validatemessage='Invalid email'>
  </div>
  <div>
  <textarea validate='length-max-10' validatemessage='Max 10 symbols'></textarea>
  </div>
  <div>
  <input type="text" validate='digits' validatemessage='Digits only'>
  </div>
  </body>
</html>
