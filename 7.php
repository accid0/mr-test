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


    function Validator(el){
      this.init(el);
    }
    
    Validator.prototype.init                          = function(el){
      this.$el                                        = el;
      this.message                                    = el.getAttribute('validatemessage');
      this.type                                       = el.getAttribute('validate');
      el.onchange                                     = this.onchange.bind(this);
    };
    
    Validator.prototype.onchange                      = function(event){
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
  
    function ValidateDigit(el){
      this.ancestor.constructor.call(this, el);
    }
  
    function ValidateLength(el){
      this.ancestor.constructor.call(this, el);
    }

    function ValidateEmail(el){
      this.ancestor.constructor.call(this, el);
    }

    extend(Validator, ValidateDigit);
    extend(Validator, ValidateLength);
    extend(Validator, ValidateEmail);

    ValidateDigit.prototype.onchange                  = function(event){
      this.ancestor.onchange.call(this, event);
    };

    ValidateDigit.prototype.init                      = function(el){
      this.ancestor.init.call(this, el);
      this.reg                                        = /^\d+$/;
    };

    ValidateLength.prototype.onchange                 = function(event){
      this.ancestor.onchange.call(this, event);
    };

    ValidateLength.prototype.init                     = function(el){
      this.ancestor.init.call(this, el);
      var data                                        = this.type.split('-');
      this.reg                                        = new RegExp('^.{0,' + data[2] + '}$');
    };

    ValidateEmail.prototype.onchange                  = function(event){
      this.ancestor.onchange.call(this, event);
    };

    ValidateEmail.prototype.init                      = function(el){
      this.ancestor.init.call(this, el);
      this.reg                                        = /^[^@]+@.+?\.[a-z]{2,}$/i;
    };

    $el.map(function(el){
      switch(test = el.getAttribute('validate')){
        case 'digits':
        new ValidateDigit(el);
        break;
        case 'email':
        new ValidateEmail(el);
        break;
        default:
        new ValidateLength(el);
      }
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
