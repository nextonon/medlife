{"version":3,"sources":["menuitemform.bundle.js"],"names":["this","BX","Landing","UI","exports","main_core","landing_ui_form_baseform","_templateObject5","data","babelHelpers","taggedTemplateLiteral","_templateObject4","_templateObject3","_templateObject2","_templateObject","depthKey","Symbol","onHeaderClick","onTextChange","MenuItemForm","_BaseForm","inherits","_this","options","arguments","length","undefined","classCallCheck","possibleConstructorReturn","getPrototypeOf","call","setEventNamespace","cache","Cache","MemoryCache","bind","assertThisInitialized","onRemoveButtonClick","Dom","addClass","layout","append","getHeaderLeftLayout","header","getHeaderRightLayout","setDepth","depth","_this$fields","slicedToArray","fields","firstField","_firstField$getValue","getValue","text","setTitle","Event","input","getHeader","createClass","key","value","event","preventDefault","isFormShown","hideForm","showForm","_this$fields2","_firstField$getValue2","emit","form","remove","style","body","removeClass","getDragButton","remember","Tag","render","getTitleLayout","_this2","Text","encode","title","_this3","getRemoveButton","_this4","button","_this5","Type","isString","isNumber","innerText","decode","offset","toNumber","concat","attr","getDepth","serialize","_this$fields3","BaseForm","Form"],"mappings":"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","file":"menuitemform.bundle.map.js"}