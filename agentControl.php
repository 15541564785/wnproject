<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
/**
 *
 *   FILE : agentControl.php
 *   Date : 2016-11-08 09:53
 *
 *  Class : agentControl
 *
 **/
class agentControl extends control {
    public $session;
    public $lang;

    private $customerID;
    private $contactID;
    private $userType;

    private $pageName;

    public function __construct() {
        parent::__construct();

        global $csession;
        $this->session = $csession;

        /* 填写页面名称获取权限 */
        $this->pageName = 'agent';

        $this->loadService('security');
        $this->loadService('security', 'acl');
        $this->loadService('user', 'customer');

        $this->loadService('service', 'agent');
        $this->loadService('service', 'agentGroup');
        $this->loadService('pbx', 'extension');

        $this->customerID = $this->session->getUserData("customerID");
        $this->contactID  = $this->session->getUserData("contactID");
        $this->userType   = $this->session->getUserData("userType");

        $this->agent->setCustomerData($this->customerID, $this->userType, $this->contactID);
        $this->agentGroup->setCustomerData($this->customerID, $this->userType, $this->contactID);
        $this->extension->setCustomerData($this->customerID, $this->userType, $this->contactID);

        $this->getLanguage();
    }

    /**
     * 页面初始化, 返回各种控制信息.如需显示模块数据,请一并获取
     *
     * @return 错误码
     */
    public function index() {
        $init = Array(
            'result'      => Array('error' => E_SUCC),
            'lang'        => $this->lang,
            'priv'        => $this->acl->getPrivilege($this->pageName),
        );

        echo json_encode($init);
        return E_SUCC;
    }

    /**
     * 查询单条数据
     *
     * 请不要格式化里面的内容
     *
     * @return 错误码
     */
    public function get() {
        $id = -1;

        $id = $this->security->postIntVal("id");
        if ($id === FALSE || $id == '') {
            return $this->error->outputError(E_C_INVALID_PARAM, Array('field' => 'id', 'value' => $id ));
        }

        $r = $this->agent->get($id);
        if (!is_array($r)) {
            return $this->error->outputError($r);
        }

        if (is_null($r['Telephone'])) {
            $r['Telephone'] = "";
        }

        if (is_null($r['TTNumber'])) {
            $r['TTNumber'] = "";
        }

        if (is_null($r['Mobile'])) {
            $r['Mobile'] = "";
        }

        return $this->error->outputError(E_SUCC, $r);
    }

    /**
     * 删除数据
     *
     * @return 错误码
     */
    public function delete() {
        $ids = $this->security->postJSONString('ids');
        if ($ids == FALSE || count($ids) <= 0) {
            return $this->error->outputError(E_C_INVALID_PARAM, Array('field' => 'ids'));
        }

        $ids = $this->security->checkArrayID($ids);
        if ($ids === FALSE || count($ids) <= 0) {
            return $this->error->outputError(E_C_INVALID_PARAM, Array('field' => 'ids invalid'));
        }

        $r = $this->agent->delete($ids);

        return $this->error->outputError($r);
    }

    /**
     * 编辑数据,请根据里面特定的值确定是添加还是更新
     *
     * @return 错误码
     */
    public function edit() {
        $id = $this->security->postIntVal("id");

        /* 获取页面传过来的数据，并作简单校验 */
        $StaffNo = $this->security->postStringVal('StaffNo', OBJ_STAFFNO_LENGTH);
        $Type = $this->security->postIntVal("Type");
        $Name = $this->security->postStringVal('Name', OBJ_NAME_LENGTH);
        $Password = $this->security->postStringVal('Password', OBJ_PASSWORD_LENGTH);
        $AgentGroup1 = $this->security->postIntVal("AgentGroup1");
        $AgentGroup2 = $this->security->postIntVal("AgentGroup2");
        $Recording = $this->security->postIntVal("Recording");
        $Popup = $this->security->postIntVal("Popup");
        $ACW = $this->security->postIntVal("ACW");
        $status = $this->security->postIntVal("status");
        $OnlineCall = $this->security->postIntVal("OnlineCall");
        $OfflineCall = $this->security->postIntVal("OfflineCall");
        $Extension = $this->security->postIntVal("Extension");
        $Telephone = $this->security->postStringVal('Telephone', OBJ_TELNUM_LENGTH);
        $Mobile = $this->security->postStringVal('Mobile', OBJ_TELNUM_LENGTH);
        $TTNumber = $this->security->postStringVal('TTNumber', OBJ_TELNUM_LENGTH);

        /* 收集数据 */
        $data = Array(
            'StaffNo' => $StaffNo,
            'Type' => $Type,
            'Name' => $Name,
            'Password' => $Password,
            'AgentGroup1' => $AgentGroup1,
            'AgentGroup2' => $AgentGroup2,
            'Recording' => $Recording,
            'Popup' => $Popup,
            'ACW' => $ACW,
            'status' => $status,
            'OnlineCall' => $OnlineCall,
            'OfflineCall' => $OfflineCall,
            'Extension' => $Extension,
            'Telephone' => $Telephone,
            'Mobile' => $Mobile,
            'TTNumber' => $TTNumber,
        );

        $r = $this->agent->edit($id, $data);
        if (!is_array($r)) {
            return $this->error->outputError($r);
        }

        return $this->error->outputError(E_SUCC, $r);
    }


    public function editBatch() {
        $agents = $this->security->postJSONString("agents");

        $AgentGroup1 = $this->security->postIntVal("AgentGroup1");
        $AgentGroup2 = $this->security->postIntVal("AgentGroup2");
        $Recording = $this->security->postIntVal("Recording");
        $Popup = $this->security->postIntVal("Popup");
        $ACW = $this->security->postIntVal("ACW");
        $status = $this->security->postIntVal("status");

        $Password = $this->security->postStringVal('Password', OBJ_PASSWORD_LENGTH);

        $statusDisabled = $this->security->postBoolVal('statusChecked', TRUE);
        $popupDisabled = $this->security->postBoolVal('popupChecked', TRUE);
        $recordingDisabled = $this->security->postBoolVal('recordingChecked', TRUE);
        $agentGroup1Disabled = $this->security->postBoolVal('agentGroup1Checked', TRUE);
        $agentGroup2Disabled = $this->security->postBoolVal('agentGroup2Checked', TRUE);

        $data = Array(
            'AgentGroup1' => $AgentGroup1,
            'AgentGroup2' => $AgentGroup2,
            'Recording' => $Recording,
            'Popup' => $Popup,
            'ACW' => $ACW,
            'Password' => $Password,
            'status' => $status,
            'statusDisabled' => $statusDisabled,
            'popupDisabled' => $popupDisabled,
            'recordingDisabled' => $recordingDisabled,
            'agentGroup1Disabled' => $agentGroup1Disabled,
            'agentGroup2Disabled' => $agentGroup2Disabled,
        );

        $r = $this->agent->editBatch($agents, $data);
        return $this->error->outputError($r);
    }

    public function addBatch() {
        $StaffNo = $this->security->postIntVal("StaffNo");
        $Type = $this->security->postIntVal("Type");
        $number = $this->security->postIntVal("number");
        $Password = $this->security->postStringVal('Password', OBJ_PASSWORD_LENGTH);

        $AgentGroup1 = $this->security->postIntVal("AgentGroup1");
        $AgentGroup2 = $this->security->postIntVal("AgentGroup2");
        $Recording = $this->security->postIntVal("Recording");
        $Popup = $this->security->postIntVal("Popup");
        $ACW = $this->security->postIntVal("ACW");
        $status = $this->security->postIntVal("status");
        $bindExtension = $this->security->postIntVal("bindExtension");

        $data = Array(
            'StaffNo' => $StaffNo,
            'Type' => $Type,
            'number' => $number,
            'Password' => $Password,
            'AgentGroup1' => $AgentGroup1,
            'AgentGroup2' => $AgentGroup2,
            'Recording' => $Recording,
            'Popup' => $Popup,
            'ACW' => $ACW,
            'status' => $status,
            'bindExtension' => $bindExtension
        );

        $r = $this->agent->addBatch($data);
        if (!is_array($r)) {
            return $this->error->outputError($r);
        }
        return $this->error->outputError(E_SUCC, $r);
    }

    public function import() {
        @ini_set('memory_limit', '1024M');
        $uploader = $this->app->loadClass("uploader");
        $files = $uploader->uploadFile("file", Array('xlsx', 'xls'), CC_TMP_FILE_PATH);
        if ($files === false || !is_array($files) || count($files) <= 0) {
            return $this->error->outputError(E_G_UPLOAD_FAIL, $uploader->getErrorMsg());
        }

        $fileName = $files[0];

        $hFile = $this->app->loadClass('file');
        $hFile->open($fileName, "r");
        $data = $hFile->read();
        $hFile->close();
        if ($data === FALSE) {
            return $this->error->outputError(E_G_READ_FILE_FAIL);
        }

        $r = $this->agent->import($data);

        if (!is_array($r)) {
            return $this->error->outputError($r);
        }

        return $this->error->outputError(E_SUCC, $r);
    }

    /**
     * 下载模块
     *
     * @return int
     */
    public function downloadTemplete() {
        $fileName = CC_AGENT_FILE_PREFIX . '.xlsx';

        $data = file_get_contents(CC_TEMPLETE_FILE_PATH . '/' . $fileName);
        if ($data === FALSE) {
            $this->error->writeLog(CC_TEMPLETE_FILE_PATH . '/' . $fileName);
            return $this->error->outputError(E_G_READ_FILE_FAIL);
        }

        $downloader = $this->app->loadClass("download");
        $r = $downloader->download($fileName, $data);
        if ($r === FALSE ) {
            $this->error->writeLog($r);
            return $this->error->outputError(E_G_READ_FILE_FAIL);
        }

        return E_SUCC;
    }

    /**
     * 导出所以坐席数据
     * @return int
     */
    public function export() {
        $pages = Array(
            'offset' => 0,
            'rows'   => 1000
        );

        $filter   = Array();
        $title = Array();

        $title[] = $this->lang['StaffNo'];
        $title[] = $this->lang['Name'];
        $title[] = $this->lang['APIPassword'];

        $file = $this->app->loadClass('file');
        @ini_set('memory_limit', '1024M');
        @ini_set('max_execution_time', '600');
        $fileName = "Agent-" . date("YmdHis") . '.xlsx';

        /* 打开文件 */
        $file->open($fileName, "w+", Array('cache' => 'php://temp'));

        /* 写入标题 */
        $file->write(Array($title));

        /* 分段读取数据 */
        do {
            /* 数据库查询 */
            $r = $this->agent->query4Export($pages, $filter, $sorter);
            if (!is_array($r)) {
                return $this->error->outputError($r);
            }

            /* 格式化数据，并且写入 */
            $r['rows'] = $this->formatRecords($r['rows']);
            $file->write($r['rows']);

            /* 维护一下分段加载 */
            if ($pages['rows'] == count($r['rows'])) {
                $pages['offset'] += count($r['rows']);
                unset($r);
            } else {
                break;
            }
        } while(1);

        /* 下载 */
        $file->download();
    }


    /**
     * 查询可用的班组列表
     * @return int
     */
    public function queryAgentGroupList() {
        $r = $this->agentGroup->getGroupList();
        if (!is_array($r)) {
            return $this->error->outputError($r);
        }

        return $this->error->outputError(E_SUCC, $r);
    }

    /**
     * 查询可用的分机号
     * @return int
     */
    public function queryExtension() {
        $id = $this->security->getIntVal("id");
        $r = $this->extension->queryExtension();
        if (!is_array($r)) {
            return $this->error->outputError($r);
        }

        $entensionEnabled = Array();
        $entensionDisable = Array();
        foreach ($r as $row) {
            if ($row['status'] == STATUS_DISABLE || $row['id'] == $id) {
                $entensionEnabled[] = Array(
                    'id'      => $row['id'],
                    'name'    => $row['extension'] . '(' . $row['userID'] . ')',
                );
            } else {
                $entensionDisable[] = Array(
                    'id'      => $row['id'],
                    'name'    => $row['extension'] . '(' . $row['userID'] . ')',
                    'disabled' => true
                );
            }
        }

        return $this->error->outputError(E_SUCC, array_merge($entensionEnabled, $entensionDisable));
    }

    /**
     * 查询坐席
     *
     * @return int
     */
    public function queryAgent() {
        $r = $this->agent->queryAgent();
        if (!is_array($r)) {
            return $this->error->outputError($r);
        }

        $result = Array();
        foreach ($r as $row) {
            $result[] = Array(
                'id'   => $row['id'],
                'name' => $row['StaffNo'] . '-' . $row['Name']
            );
        }

        return $this->error->outputError(E_SUCC, $result);
    }

    /**
     * 查询记录
     *
     * @return 错误码
     */
    public function query() {
        $filter = Array();
        $sorter = Array();

        $p = $this->security->postJSONString("p");
        $pages = $this->security->checkPagination($p);

        if (isset($p['filter'])) {
            $filter = $p['filter'];
        }

        if (isset($p['sorter'])) {
            $sorter = $p['sorter'];
        }

        $r = $this->agent->query($pages, $filter, $sorter);
        if (!is_array($r)) {
            return $this->error->outputError($r);
        }

        $r['rows'] = $this->formatRecords($r['rows']);

        return $this->error->outputError(E_SUCC, $r);
    }

    /**
     * 获取语言
     *
     * @return Array()
     */
    private function getLanguage() {
        $lang = $this->session->getUserData("clientLang");
        $this->lang = read_lang($lang);

        return $this->lang;
    }

    /**
     * 格式化记录
     *
     * @param array $data 需要格式化的数据
     *
     * @return Array()
     */
    private function formatRecords($data) {
        return $data;
    }
}
