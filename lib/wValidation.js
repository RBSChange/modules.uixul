/**
 * @license
 * Logiciel RBS Change© Société RBS, 2006-2007.
 * Le logiciel ne peut être copié, corrigé, traduit ou modifié sans l'autorisation
 * préalable de l'auteur selon le Code de la Propriété Intellectuelle (http://www.celog.fr/cpi/).
 * Consulter les Dispositions Générales de droit d'exploitation.
 * Tout contrefacteur pourra faire l’objet de poursuites judiciaires par la société RBS, auteur du logiciel.
 * --
 * RBS Change™, © 2006-2007 Ready Business System.
 * This application can not be copied, changed, translated, or modified in any way without
 * prior authorization from RBS, the author of the application, according to the French Code
 * of Intellectual Property (http://www.celog.fr/cpi/). Consult the Code's General Dispositions
 * about rights of use.
 * Any use of this application without prior authorization from RBS will be subject to legal
 * prosecution to the full extent of the law.
 *
 * @copyright RBS 2006-2007
 * @date Wed Feb 28 16:39:42 CET 2007
 * @author INTbonjF
 *
 * Client-side forms validation.
 * These classes use the same constraints format as the server ones :)
 */
var wValidation = {

    validatorExists: function(shortName)
    {
        var validatorName = this.getValidatorClassName(shortName);
        try
        {
            return getClassName(eval(validatorName)) == 'Function';
        } 
        catch (e) 
        {
        	//Validator not found
        }
        return false;
    },

    getValidator: function(shortName)
    {
        if (this.validatorExists(shortName))
        {
            var v;
            eval('v = new '+this.getValidatorClassName(shortName)+'();');
            return v;
        }
        return null;
    },

    getValidatorClassName: function(shortName)
    {
        return 'validation_' + shortName.substring(0, 1).toUpperCase() + shortName.substring(1, shortName.length) + 'Validator';
    },

    getValidatorParameter: function(param)
    {
        param = trim(param);
    	var m;
        var re = /^(\-?[0-9]+)\.\.(\-?[0-9]+)$/;
        if (m = re.exec(param))
        {
            return new validation_Range(parseInt(m[1]), parseInt(m[2]));
        }
        var re = /^(\-?[0-9]+\.[0-9]+)\.\.(\-?[0-9]+\.[0-9]+)$/;
        if (m = re.exec(param))
        {
            return new validation_Range(parseFloat(m[1]), parseFloat(m[2]));
        }
        if (param === true)
        {
        	return 'true';
        }
        if (param === false)
        {
        	return 'false';
        }
        if (param.charAt(0) == '[' && param.charAt(param.length-1) == ']')
        {
			var str = param.substring(1, param.length-1);
			var quoted = -1;
			var values = [ ];
			var value = '';
			var escaped = false;
			for (var i = 0 ; i<str.length ; i++)
			{
				var c = str.charAt(i);

				if (c == '\\')
				{
					if (escaped)
					{
						value += '\\';
					}
					escaped = ! escaped;
				}
				else
				{
					if (escaped)
					{
						if (c == '"' || c == "'")
						{
							value += c;
						}
						else
						{
							value += '\\' + c;
						}
						escaped = false;
					}
					else if (c == '"')
					{
						if (quoted == -1)
						{
							quoted = i;
						}
						else
						{
							quoted = -1;
							values.push(value);
							value = '';
						}
					}
					else if (c != ',' || quoted != -1)
					{
						value += c;
					}
				}
			}
			return values;
        }
        return param.toString();
    }
}


/**
 * Helper function to get the 'classname' of an object.
 */
function getClassName(obj)
{
    if (obj === null) {
        return 'null';
    }
    if (obj === undefined) {
        return 'undefined';
    }
    var c = obj.constructor.toString();
    return c.substring(9, c.indexOf('(', 10));
}


/**
 * Class validation_Property
 */
function validation_Property(name, value)
{
    this.name  = name;
    this.value = value;
}


/**
 * Class validation_Errors
 */
function validation_Errors()
{
	this.rejectValue = function(name, message)
	{
		this.push(message.replace('{field}', name));
	}
}
validation_Errors.prototype = new Array();


/**
 * Class validation_Range
 */
function validation_Range(min, max)
{
    this.min = Math.min(min, max);
    this.max = Math.max(min, max);

    this.toString = function()
    {
        return 'new validation_Range('+min+', '+max+')';
    }
}


/**
 * Validators base class.
 */
function validation_Validator()
{
	this.parameter = null;

    // must be overriden
    this.doValidate = function(value, errors)
    {
        throw new Error("Please implement the doValidate() method.");
    }

    this.getMessage = function()
    {
    	return this.message.replace('{param}', this.parameter.toString());
    },

    // final: do not override it!
    this.validate = function(value, errors)
    {
        var count = errors.length;
		this.doValidate(value, errors);
        return errors.length === count;
	}

    // final: do not override it!
    this.setParameter = function(p)
    {
        this.parameter = p;
    }

    // final: do not override it!
    this.reject = function(propertyName, errors)
    {
        errors.rejectValue(propertyName, this.getMessage());
    }
}


/**
 * BlankValidator
 */
function validation_BlankValidator()
{
    this.message = '&framework.validation.validator.Blank.message;';
	this.doValidate = function(property, errors)
    {
        if (trim(property.value).length == 0) {
            this.reject(property.name, errors);
        }
    }
}
validation_BlankValidator.prototype = new validation_Validator();


/**
 * SizeValidator
 */
function validation_SizeValidator()
{
    this.message = '&framework.validation.validator.Size.message;';
	this.doValidate = function(property, errors)
    {
        var length = property.value.length;
        if (this.parameter instanceof validation_Range)
        {
            if (length < this.parameter.min || length > this.parameter.max)
            {
                this.reject(property.name, errors);
            }
        }
        else
        {
            var requiredLength = parseInt(this.parameter);
            if (length != requiredLength)
            {
                this.reject(property.name, errors);
            }
        }
    }

    this.getMessage = function()
    {
    	return this.message.replace('{min}', this.parameter.min.toString()).replace('{max}', this.parameter.max.toString());
    }
}
validation_SizeValidator.prototype = new validation_Validator();


/**
 * MinSizeValidator
 */
function validation_MinSizeValidator()
{
    this.message = '&framework.validation.validator.MinSize.message;';
	this.doValidate = function(property, errors)
    {
        if (property.value.length < this.parameter)
        {
            this.reject(property.name, errors);
        }
    }
}
validation_MinSizeValidator.prototype = new validation_Validator();


/**
 * MaxSizeValidator
 */
function validation_MaxSizeValidator()
{
    this.message = '&framework.validation.validator.MaxSize.message;';
	this.doValidate = function(property, errors)
    {
        if (property.value.length > this.parameter)
        {
            this.reject(property.name, errors);
        }
    }
}
validation_MaxSizeValidator.prototype = new validation_Validator();


/**
 * MinValidator
 */
function validation_MinValidator()
{
    this.message = '&framework.validation.validator.Min.message;';
	this.doValidate = function(property, errors)
    {
        if (parseFloat(property.value) < parseFloat(this.parameter))
        {
            this.reject(property.name, errors);
        }
    }
}
validation_MinValidator.prototype = new validation_Validator();


/**
 * MaxValidator
 */
function validation_MaxValidator()
{
    this.message = '&framework.validation.validator.Max.message;';
	this.doValidate = function(property, errors)
    {
        if (parseFloat(property.value) > parseFloat(this.parameter))
        {
            this.reject(property.name, errors);
        }
    }
}
validation_MaxValidator.prototype = new validation_Validator();


/**
 * BeginsWithValidator
 */
function validation_BeginsWithValidator()
{
    this.message = '&framework.validation.validator.BeginsWith.message;';
	this.doValidate = function(property, errors)
    {
    	var l = this.parameter.length;
        if (property.value.substring(0, l) != this.parameter)
        {
            this.reject(property.name, errors);
        }
    }
}
validation_BeginsWithValidator.prototype = new validation_Validator();


/**
 * EndsWithValidator
 */
function validation_EndsWithValidator()
{
    this.message = '&framework.validation.validator.EndsWith.message;';
	this.doValidate = function(property, errors)
    {
    	var value = trim(property.value);
    	var pl = this.parameter.length;
    	var vl = value.length;
        if (value.substring(Math.max(0, vl-pl), vl) != this.parameter)
        {
            this.reject(property.name, errors);
        }
    }
}
validation_EndsWithValidator.prototype = new validation_Validator();


/**
 * NotEqualValidator
 */
function validation_NotEqualValidator()
{
    this.message = '&framework.validation.validator.NotEqual.message;';
	this.doValidate = function(property, errors)
    {
        if (property.value == this.parameter)
        {
            this.reject(property.name, errors);
        }
    }
}
validation_NotEqualValidator.prototype = new validation_Validator();


/**
 * EqualsValidator
 */
function validation_EqualsValidator()
{
    this.message = '&framework.validation.validator.Equals.message;';
	this.doValidate = function(property, errors)
    {
        if (property.value != this.parameter)
        {
            this.reject(property.name, errors);
        }
    }
}
validation_EqualsValidator.prototype = new validation_Validator();


/**
 * RangeValidator
 */
function validation_RangeValidator()
{
    this.message = '&framework.validation.validator.Range.message;';
	this.doValidate = function(property, errors)
    {
    	var v = trim(property.value);
    	var re = new RegExp('^\-?[0-9]+(\.[0-9]+)?$');
    	if (!v.match(re))
    	{
    		this.reject(property.name, errors);
    	}
    	else
    	{
	    	v = parseFloat(v);
	    	if (isNaN(v))
	    	{
	    		this.reject(property.name, errors);
	    	}
	    	else if (v < this.parameter.min || v > this.parameter.max)
	        {
	            this.reject(property.name, errors);
	        }
    	}
    }

    this.getMessage = function()
    {
    	return this.message.replace('{min}', this.parameter.min.toString()).replace('{max}', this.parameter.max.toString());
    }
}
validation_RangeValidator.prototype = new validation_Validator();


/**
 * MatchesValidator
 */
function validation_MatchesValidator()
{
    this.message = '&framework.validation.validator.Matches.message;';
	this.doValidate = function(property, errors)
    {
    	var v = trim(property.value);
    	var expressionParts = this.parameter.split('#');
    	if (expressionParts.length == 1)
    	{
    	  	var re = new RegExp(this.parameter);
    		if (!v.match(re))
    		{
    			this.reject(property.name, errors);
    		}
    	}
    	else
    	{		
    		var re = new RegExp(expressionParts[0]);
    		if (!v.match(re))
    		{
    			this.message = new wServerLocale(expressionParts[1]).toString();	
    			this.reject(property.name, errors);
    		}
    	}
    	
    }
}
validation_MatchesValidator.prototype = new validation_Validator();


/**
 * InListValidator
 */
function validation_InListValidator()
{
    this.message = '&framework.validation.validator.InList.message;';
	this.doValidate = function(property, errors)
    {
    	var v = trim(property.value);
    	var r = false;
    	for (var i=0 ; i<this.parameter.length && !r ; i++)
    	{
    		r = (v == this.parameter[i]);
    	}
    	if (!r)
    	{
    		this.reject(property.name, errors);
    	}
    }
}
validation_InListValidator.prototype = new validation_Validator();


/**
 * EmailValidator
 */
function validation_EmailValidator()
{
    this.message = '&framework.validation.validator.Email.message;';
	this.doValidate = function(property, errors)
    {
    	if (!trim(property.value).match(this.getRegExp()))
    	{
    		this.reject(property.name, errors);
    	}
    }
    this.getRegExp = function()
    {
    	return /^[a-z0-9][a-z0-9_.-]*@[a-z0-9][a-z0-9.-]*\.[a-z]{2,}$/i;
    }
}
validation_EmailValidator.prototype = new validation_Validator();


/**
 * EmailsValidator
 */
function validation_EmailsValidator()
{
    this.message = '&framework.validation.validator.Emails.message;';
	this.doValidate = function(property, errors)
    {
    	var v = trim(property.value).split(/,\s*/);
    	var re = new validation_EmailValidator().getRegExp();
    	for (var i=0 ; i < v.length ; i++)
    	{
	    	if (!v[i].match(re))
	    	{
	    		this.reject(property.name, errors);
	    	}
    	}
    }
}
validation_EmailsValidator.prototype = new validation_Validator();


/**
 * UrlValidator
 */
function validation_UrlValidator()
{
    this.message = '&framework.validation.validator.Url.message;';
	this.doValidate = function(property, errors)
    {
    	var v = trim(property.value);
    	var re = new RegExp('^[a-z]+:\/\/[a-z0-9\-\.]+\.[a-z0-9]+(\/.*)?$', 'i');
    	if (!v.match(re))
    	{
    		this.reject(property.name, errors);
    	}
    }
}
validation_UrlValidator.prototype = new validation_Validator();


/**
 * HostValidator
 *//*
function validation_HostValidator()
{
    this.message = '&validation.validator.Host.message;';
	this.doValidate = function(property, errors)
    {
    	var v = trim(property.value);
    	var re = new RegExp('^[a-z]+:\/\/([a-z0-9]+[\-\.]?)+[a-z0-9]$', 'i');
    	if (!v.match(re))
    	{
    		this.reject(property.name, errors);
    	}
    }
}
validation_HostValidator.prototype = new validation_Validator();
*/


/**
 * IntegerValidator
 */
function validation_IntegerValidator()
{
    this.message = '&framework.validation.validator.Integer.message;';
	this.doValidate = function(property, errors)
    {
    	var v = trim(property.value);
    	var re = /^(\-?\d+)?$/;
    	if (!v.match(re))
    	{
    		this.reject(property.name, errors);
    	}
    }
}
validation_IntegerValidator.prototype = new validation_Validator();


/**
 * FloatValidator
 */
function validation_FloatValidator()
{
    this.message = '&framework.validation.validator.Float.message;';
    this.regexp = '&framework.validation.validator.Float.regexp;';
    if (this.regexp == 'regexp') {this.regexp = '^([\\-+]?)(\\d{0,8})?[\\.,]?(\\d{0,8})?$';}
	this.doValidate = function(property, errors)
    {
    	var v = trim(property.value);
    	var re = new RegExp(this.regexp);
    	if (!re.test(v))
    	{
    		this.reject(property.name, errors);
    	}
    }
}
validation_FloatValidator.prototype = new validation_Validator();


///////////////////////////////////////////////////////////////////////////////
//                                                                           //
//  intbonjf 2007-03-05                                                      //
//  BACKWARD COMPATIBILITY FOR DATEPICKER WIDGET.                            //
//  The following has been taken from the old 'wValidation.js' file          //
//    but I'm not the author...                                              //
//  Is this still needed??                                                   //
//                                                                           //
///////////////////////////////////////////////////////////////////////////////


function validation_IsLocalizedDateValidator()
{
	this.message = '&framework.validation.validator.IsLocalizedDate.message;';
	this.doValidate = function(property, errors)
    {
    	var strFormatDate = this.parameter;
    	var strValue = property.value;
    	var checkResult = false;

		// If the length of strValue is equal at the length of strFormatDate, the date can be write correctly
		// and we can check if she is ok else she can't be OK.
		if (strValue.length >= strFormatDate.length)
		{

			var stringFormat = strFormatDate;

			// Construct the regEx
			var nbDay = 0;
			var nbMonth = 0;
			var nbYear = 0;
			var nbHour = 2;
			var nbMinute = 2;
			var nbSeconde = 2;
			var year;
			var month;
			var day;
			var hour = null;
			var minute = null;
			var second = null;
			var valuePosition = 0;
			var stringFormatLength = stringFormat.length;
			var order = new Array();

			for (var i=0; i<stringFormatLength; i++)
			{
				var addChain = "";

				switch (stringFormat[i]){

					case 'd' :
						nbDay = 2;
						addChain = "nbDay";
						day = strValue.substring( valuePosition, valuePosition + 2 );
						valuePosition = valuePosition + 2;
						break;

					case 'j' :
						nbDay = '1,2';
						addChain = "nbDay";
						if( ! isNaN( strValue.substring( valuePosition, valuePosition + 2 ) ) )
						{
							day = strValue.substring( valuePosition, valuePosition + 2 );
							valuePosition = valuePosition + 2;
						}
						else
						{
							day = strValue.substring( valuePosition, valuePosition + 1 );
							valuePosition = valuePosition + 1;
						}
						break;

					case 'm' :
						nbMonth = 2;
						addChain = "nbMonth";
						month = strValue.substring( valuePosition, valuePosition + 2 );
						valuePosition = valuePosition + 2;
						break;

					case 'n' :
						nbMonth = '1,2';
						addChain = "nbMonth";
						if( ! isNaN( strValue.substring( valuePosition, valuePosition + 2 ) ) )
						{
							month = strValue.substring( valuePosition, valuePosition + 2 );
							valuePosition = valuePosition + 2;
						}
						else
						{
							month = strValue.substring( valuePosition, valuePosition + 1 );
							valuePosition = valuePosition + 1;
						}
						break;

					case 'Y' :
						nbYear = 4;
						addChain = "nbYear";
						year = strValue.substring( valuePosition, valuePosition + 4 );
						valuePosition = valuePosition + 4;
						break;

					case 'y' :
						nbYear = 2;
						addChain = "nbYear";
						year = strValue.substring( valuePosition, valuePosition + 2 );
						valuePosition = valuePosition + 2;
						break;

					case 'H' :
						addChain = "nbHour";
						hour = strValue.substring( valuePosition, valuePosition + 2 );
						valuePosition = valuePosition + 2;
						break;

					case 'i' :
						addChain = "nbMinute";
						minute = strValue.substring( valuePosition, valuePosition + 2 );
						valuePosition = valuePosition + 2;
						break;

					case 's' :
						addChain = "nbSeconde";
						seconde = strValue.substring( valuePosition, valuePosition + 2 );
						valuePosition = valuePosition + 2;
						break;

					default :
						addChain = stringFormat[i];
						valuePosition = valuePosition + 1;
						break;

				}

				if (order[order.length-1] != addChain)
				{
					order.push(addChain);
				}
			}

			var regEx = '^';
			for (var i=0; i<order.length; i++)
			{
				if(order[i]=='nbDay' || order[i]=='nbMonth' || order[i]=='nbYear' || order[i]=='nbHour' || order[i]=='nbMinute' || order[i]=='nbSeconde')
				{
					regEx = regEx+'\\d{'+eval(order[i])+'}';
				}
				else
				{
					regEx = regEx+'\\'+order[i];
				}
			}
			regEx = regEx+'$';
			// Construct the regEx End

			// Validate the day, month and year if format is valid
			month = Number(month);
			day = Number(day);
			year = Number(year);
			if (hour != null) hour = Number(hour);
			if (minute != null) minute = Number(minute);
			if (second != null) second = Number(seconde);
			regExpObject = new RegExp(regEx);

			if (strValue.match(regExpObject))
			{
				checkResult = true;

				if (year < 1000)
				{
					checkResult = false;
				}

				if (day < 1)
				{
					checkResult = false;
				}

				if (month > 0 && month < 13)
				{
					switch(month)
					{
						case 1 :
						case 3 :
						case 5 :
						case 7 :
						case 8 :
						case 10 :
						case 12 :
							if (day > 31)
							{
								checkResult = false;
							}
							break;

						case 4 :
						case 6 :
						case 9 :
						case 11 :
							if (day > 30)
							{
								checkResult = false;
							}
							break;

						case 2 :
							if (year%4==0 && day > 29)
							{
								checkResult = false;
							}
							if (year%4!=0 && day > 28)
							{
								checkResult = false;
							}
							break;
					}
				}
				else
				{
					checkResult = false;
				}

				if (hour != null && hour < 0 && hour > 23)
				{
					checkResult = false;
				}
				if (minute != null && minute < 0 && minute > 60)
				{
					checkResult = false;
				}
				if (second != null && second < 0 && second > 60)
				{
					checkResult = false;
				}

			}
		}
	    if (!checkResult)
	    {
			this.reject(property.name, errors);
	    }
    }
}
validation_IsLocalizedDateValidator.prototype = new validation_Validator();