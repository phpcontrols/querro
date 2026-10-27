<?php
namespace phpCtrl;

// Desc: builds the SQL WHERE clause for grid search requests (data.php, export.php).
// Handles both jqGrid simple search (column=value request params) and the
// toolbar/advanced search "filters" JSON. Column names, operators and groupOp are
// whitelisted; every user-supplied value is returned as a bound parameter and never
// becomes part of the SQL text.
class C_SearchFilter{

    // jqGrid search operator => [SQL template, value pattern]. '%s' in the pattern is
    // replaced by the user value before binding; 'in'/'ni' take a comma-separated list.
    const OPERATORS = array(
        'eq' => array('= ?',        '%s'),
        'ne' => array('<> ?',       '%s'),
        'lt' => array('< ?',        '%s'),
        'le' => array('<= ?',       '%s'),
        'gt' => array('> ?',        '%s'),
        'ge' => array('>= ?',       '%s'),
        'bw' => array('LIKE ?',     '%s%%'),
        'bn' => array('NOT LIKE ?', '%s%%'),
        'ew' => array('LIKE ?',     '%%%s'),
        'en' => array('NOT LIKE ?', '%%%s'),
        'cn' => array('LIKE ?',     '%%%s%%'),
        'nc' => array('NOT LIKE ?', '%%%s%%'),
        'in' => array('IN',         '%s'),
        'ni' => array('NOT IN',     '%s'),
    );

    // ADOdb metatypes searched with '=' rather than a prefix LIKE in simple search
    const NUMERIC_METATYPES = array('I', 'N', 'R', 'SERIAL', 'L');

    const GROUP_OPS = array('AND', 'OR');

    // $request:     search params, normally $_REQUEST
    // $allowed_cols: filterable column names, normally C_DataBase::get_col_dbnames()
    // $metatype_of: callable(string $col): string — ADOdb metatype for a bare column name
    // Returns array('sql' => clause without leading WHERE ('' when no search), 'params' => bound values)
    public static function build(array $request, array $allowed_cols, callable $metatype_of){
        $clauses = array();
        $params  = array();

        foreach($request as $key => $value){
            if(!in_array($key, $allowed_cols, true) || !is_scalar($value)){
                continue;   // not a grid column, or an array param such as status[]=x
            }
            if(in_array($metatype_of(self::bare_column($key)), self::NUMERIC_METATYPES, true)){
                $clauses[] = $key .' = ?';
                $params[]  = (string)$value;
            }else{
                $clauses[] = $key .' LIKE ?';
                $params[]  = $value .'%';
            }
        }

        if(isset($request['filters']) && $request['filters'] !== ''){
            $filter_where = self::build_filters($request['filters'], $allowed_cols);
            if($filter_where['sql'] !== ''){
                $clauses[] = $filter_where['sql'];
                $params    = array_merge($params, $filter_where['params']);
            }
        }

        return array('sql' => implode(' AND ', $clauses), 'params' => $params);
    }

    private static function build_filters($filters_json, array $allowed_cols){
        $filters = self::decode_filters($filters_json);

        // trim: older user code (copied from examples/externalize_search.php) posts " AND "
        $group_op = strtoupper(trim(isset($filters->groupOp) ? (string)$filters->groupOp : 'AND'));
        $rules    = isset($filters->rules) && is_array($filters->rules) ? $filters->rules : array();
        if(!in_array($group_op, self::GROUP_OPS, true)){
            throw new C_InvalidRequest('Invalid search group operator');
        }

        $clauses = array();
        $params  = array();
        foreach($rules as $rule){
            $field = isset($rule->field) ? (string)$rule->field : '';
            if($field === ''){
                continue;   // computed/alias columns post an empty field name
            }
            if(!in_array($field, $allowed_cols, true)){
                throw new C_InvalidRequest('Invalid field name');
            }

            $op = isset($rule->op) ? (string)$rule->op : '';
            if(!isset(self::OPERATORS[$op])){
                throw new C_InvalidRequest('Invalid search operator');
            }
            list($sql_op, $pattern) = self::OPERATORS[$op];
            $data = isset($rule->data) ? (string)$rule->data : '';

            if($op === 'in' || $op === 'ni'){
                $items     = array_map('trim', explode(',', $data));
                $clauses[] = $field .' '. $sql_op .' ('. implode(',', array_fill(0, count($items), '?')) .')';
                $params    = array_merge($params, $items);
            }else{
                $clauses[] = $field .' '. $sql_op;
                $params[]  = sprintf($pattern, $data);
            }
        }

        if(empty($clauses)){
            return array('sql' => '', 'params' => array());
        }
        return array('sql' => '('. implode(' '. $group_op .' ', $clauses) .')', 'params' => $params);
    }

    // Decode the jqGrid "filters" JSON. Falls back to stripslashes() for hosts that
    // still add magic-quotes style slashes to request data.
    private static function decode_filters($filters_json){
        $filters = json_decode((string)$filters_json);
        if(!is_object($filters)){
            $filters = json_decode(stripslashes((string)$filters_json));
        }
        if(!is_object($filters)){
            throw new C_InvalidRequest('Invalid search filters');
        }

        return $filters;
    }

    // "orders.status" => "status" — metatype lookup is by bare column name
    private static function bare_column($col){
        return (strpos($col, '.') === false) ? $col : explode('.', $col)[1];
    }
}
