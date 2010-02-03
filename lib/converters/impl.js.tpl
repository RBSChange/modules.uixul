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
 * @date <{$date}>
 *
 * This file contains the implementation of the form for documents of type "<{$documentModel}>".
 * It will be used to create a XUL binding (http://www.xulplanet.com/tutorials/xultu/introxbl.html)
 * that extends the RBS's wForm binding (located here: modules_uixul/lib/bindings/form/wForm.xml).
 *
 * Here is the list of the overridable methods you may code in this file:
 *
 * - onInit           : called when the forms has initialized successfully.
 * - onReset          : called when the fields are reset (values are restored).
 * - onEmpty          : called when the fields are made blank (empty values).
 * - onCreateNew      : called when the user wants to create a new document.
 * - onBeforeSave     : called before save() is called. If returns false, save() is cancelled and onSaveCancelled() is called.
 * - onSaveCancelled  : called when the save method could not succeed (onBeforeSave returned false).
 * - onSave           : called when the save process has succeeded.
 * - onSaveError      : called when the save process failed.
 * - onBeforeLoad     : called before load() is called. If returns false, load() is cancelled and onLoadCancelled() is called.
 * - onLoadCancelled  : called when the load method could not succeed (onBeforeLoad returned false).
 * - onLoad           : called when the load process has succeeded.
 * - onLoadError      : called when the load process failed.
 * - onBeforeClose    : called before close() is called. If returns false, close() is cancelled and onCloseCancelled() is called.
 * - onCloseCancelled : called when the close process has been cancelled (onBeforeClose returned false).
 * - onClose          : called when the form has been closed. Warning: the form is not bound to a document model anymore!
 * - setErrors        : called everytime a widget does not validate.
 *
 * It is NOT recommended to override other methods of the "form.wForm" binding.
 * Please have a look at this file BEFORE coding a method here or, at least,
 * have a look at its auto-generated documentation at:
 * http://your.project.com/index.php?module=uixul&action=Doc&binding=form.wForm
 *
 * PLEASE DO NOT COPY FORMS BETWEEN MODULES BUT USE THE BUILDER INSTEAD.
 *
 * This file has been auto-generated from the XUL binding located at:
 * "<{$generatedFrom}>"
 */

/**
 * To declare a <field/> element, use a var:
 *   var myNewField = 'default value';
 * will be transformed into:
 *   <field name="myNewField">'default value'</field>
 *
 *
 * To declare a <field readonly="true"/>, use a const:
 *   const myConst = 3;
 * will be transformed into:
 *   <field name="myConst" readonly="true">3</field>
 *
 *
 * To declare a <property/>, use one function for the getter and one for the setter:
 *   function _property_getter_myProperty()
 *   {
 *      code of your property getter:
 *      must end with a "return" statement.
 *   }
 *   function _property_setter_myProperty()
 *   {
 *      code of your property setter:
 *      the local variable 'val' holds the value to set.
 *   }
 * will be transformed into:
 *   <property name="myProperty">
 *     <getter><![CDATA[
 *       code of your property getter:
 *       must end with a "return" statement.
 *     ]]></getter>
 *     <setter><![CDATA[
 *       code of your property setter:
 *       the local variable 'val' holds the value to set.
 *     ]]></setter>
 *   </property>
 *
 *
 * To declare a <method/>, simply declare a "function" with its arguments,
 * as you would do in a classical JavaScript file.
 *
 * You may remove this comment to reduce the file length.
 */

<{$content}>