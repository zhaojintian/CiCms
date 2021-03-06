<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Content extends admin_Controller {

	private $_data = array();

	public function __construct()
	{
		parent::__construct();

		$this->load->model('model_content', 'content', TRUE);
		$this->load->model('model_category', 'category', TRUE);
		$this->load->model('model_user', 'user', TRUE);
		$this->load->library('form_validation');
		$this->load->library('tree');
		$this->_data['admin_url'] = uri_string();
		$this->_data['menu'] = $this->menu;
	}
	/**
	 * 内容列表
	 *
	 * @access public
	 * @return void
	 */
	public function index()
	{
		$this->load->library('pagination');
		$config['base_url'] = base_url() . 'admin/content/index/';
		$config['total_rows'] = $this->content->record_count();
		$config['per_page'] = 10; 
		$config['uri_segment'] = 4;
		$config['first_link']  = '首页';
        $config['last_link']   = '尾页';
		$config['next_link']   = '下一页';
		$config['prev_link']   = '上一页';
		$config['num_links']  = 1;

		$this->pagination->initialize($config); 
		$this->_data['page'] = $this->pagination->create_links();
		$page = ($this->uri->segment(4)) ? $this->uri->segment(4) : 0;
		$content_list = $this->content->get_content_list($config['per_page'], $page);
		if ($content_list) {
			foreach ($content_list as $k => $v) {
				$userinfo = $this->user->get_user_by_id($v['user_id']);
				$content_list[$k]['username'] = $userinfo['username'];
			}
			$this->_data['content_list'] = $contents_list;
		} else {
			$this->_data['content_list'] = array();
		}
		
		$this->layout->view('admin/content_list', $this->_data);
	}

	/**
	 * 发布文章
	 *
	 * @access public
	 * @return void
	 */
	public function create()
	{
		$category_list = $this->category->get_category_list();
		if ($category_list) {
			$this->tree->setTree($category_list);
			$this->_data['category_list'] = $this->tree->getTree();
		} else {
			$this->_data['category_list'] = array();
		}
		
		if ($_SERVER['REQUEST_METHOD'] === "POST")
		{
			$this->_load_validation_rules();

			if ($this->form_validation->run())
			{
				$this->content->add_content(
					array(
						'title'       => $this->input->post('title', TRUE),
						'category_id' => $this->input->post('category_id', TRUE),
						'content'     => $this->input->post('content', TRUE),
						'user_id'     => $this->admin_user['id'],
						'status'      => $this->input->post('status', TRUE),
						'allowComment' => ($this->input->post('allowComment', TRUE)) ? $this->input->post('allowComment', TRUE) : 0,
						'created'     => time()
					)
				);
				$this->session->set_flashdata('success', '成功添加一篇文章');
				go_back();
			}
		} 

		$this->layout->view('admin/content_create_view', $this->_data);
	}

	/**
	 * 更新文章
	 *
	 * @access public
	 * @return void
	 */
	public function update()
	{
		if (is_numeric($this->uri->segment(4))) {	
			$this->_id = $this->uri->segment(4);
			$content = $this->content->get_content_by_id($this->uri->segment(4));
			if ($content) {
				$this->_data['content'] = $content;
			} else {
				show_error('文章不存在或已经被删除');
			}
		} else {
			show_404();
		}

		$category_list = $this->category->get_category_list();
		if ($category_list) {
			$this->tree->setTree();
		    $this->_data['category_list'] = $this->tree->getTree();
		} else {
			$this->_data['category_list'] = array();
		}

		if ($_SERVER['REQUEST_METHOD'] === "POST")
		{
			$this->_load_validation_rules();

			if ($this->form_validation->run() != FALSE)
			{
				$this->content->update_content($this->uri->segment(4),
					array(
						'title'       => $this->input->post('title', TRUE),
						'category_id' => $this->input->post('category_id', TRUE),
						'content'     => $this->input->post('content', TRUE),
						'user_id'     => $this->admin_user['id'],
						'status'      => $this->input->post('status', TRUE),
						'allowComment' => ($this->input->post('allowComment', TRUE)) ? $this->input->post('allowComment', TRUE) : 0,
						'modified'     => time()
					)
				);
				$this->session->set_flashdata('success', '成功修改文章 '. $this->_data['content']['title']);
				go_back();
			}
		}

		$this->layout->view('admin/content_update_view', $this->_data);
	}
	/**
	 * 删除文章
	 *
	 * @access public
	 * @return bool
	 */
	public function delete()
	{
		$contents = $this->input->post('check', TRUE);
		$deleted = 0;
		if ($contents && is_array($contents))
		{
			foreach ($contents as $content)
			{   
				$query = $this->content->delete_content($content);
				if ($query)
				{
					$deleted++;
				}
				
			}
		}
		$msg = ($deleted > 0) ? '文章已经删除！' : '没有文章被删除！';
		$notify = ($deleted > 0) ? 'success' : 'error';

		$this->session->set_flashdata($notify, $msg);
		go_back();
	}
	/**
	 * 配置表单验证规则
	 *
	 * @access private
	 * @return void
	 */
	private function _load_validation_rules()
	{
		$this->form_validation->set_rules('title', '文章标题', 'trim|required|htmlspecialchars');
		$this->form_validation->set_rules('category_id', '文章分类', 'trim|required|integer|callback_check_category_id');
		$this->form_validation->set_rules('content', '文章内容', 'trim|required');
		$this->form_validation->set_rules('status', '状态', 'trim|required|integer');
	}

	public function check_category_id($id)
	{
		if ($id == 0)
		{
			$this->form_validation->set_message('check_category_id', '请选择文章分类');
			return FALSE;
		}
	} 
}