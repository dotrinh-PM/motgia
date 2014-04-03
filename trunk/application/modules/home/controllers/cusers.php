<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class cusers extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->helper(array('form', 'html', 'url'));
        $this->load->library(array('session', 'encrypt', 'email', 'my_email'));
        $this->load->model(array('Musers', 'Mlog', 'Mproducts'));
        $this->load->database();
    }

    public function index() {
        $temp['info'] = $this->Mlog->log();
        $temp['title'] = 'Trang chủ';
        $temp['data_home'] = $this->Mproducts->getAllProducts();
        $temp['data_slide'] = $this->Mproducts->getDataSlide();
        $temp['template'] = 'home';
        $this->load->view('layout/layout', $temp);
    }

    public function signup() {
        $this->load->helper(array('captcha'));
        $this->load->model('captcha_model');
        $cap = $this->captcha_model->createCaptcha();
        $l_name = $this->input->post('l_name');
        $f_name = $this->input->post('f_name');
        $month = $this->input->post('month');
        $birthday = $this->input->post('birthday');
        $gender = $this->input->post('gender');
        $phone = $this->input->post('phone');
        $province = $this->input->post('province');
        $email = $this->input->post('email');
        $psw = $this->input->post('pass');
        $pass = $this->encrypt->encode($psw);
        $adr = $this->input->post('adr');
        $name = $f_name . ' ' . $l_name;

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $sz_Word = $this->input->post('captcha');
            $b_Check = $this->captcha_model->b_fCheck($sz_Word);
            $cbx = $this->input->post('check');
            $ckmail = $this->Musers->checkMail($email);
            if ($ckmail && $b_Check && $cbx == 'ok') {
                $this->Musers->insertUser($l_name, $f_name, $month, $birthday, $gender, $phone, $province, $email, $pass, $adr);
                $userid = mysql_insert_id();
                $this->sendMail($email, $psw, $name);
                $temp['success'] = '
                    </br><span style="margin-left:88px">Bạn đã đăng ký thành công!</span>
                    </br><span style="margin-left:88px">Vui lòng kiểm tra e-mail để kích hoạt tài khoản và sử dụng dịch vụ</span>
                    </br><span style="margin-left:88px">You has been register completed ! </span>
                    </br><span style="margin-left:88px">Please check your email address to active your account and use system ! </span>
                ';
            } if ($ckmail == FALSE) {
                $temp['error1'] = 'Email này đã được sử dụng!';
            } if ($cbx != 'ok') {
                $temp['error2'] = 'Bạn không đồng ý với các điều khoản quy định của chúng tôi!';
            } if ($b_Check == FALSE) {
                $temp['error3'] = 'Mã xác nhận không đúng!';
            }
        }
        $temp['info'] = $this->Mlog->log();
        $temp['Data'] = $cap['image'];
        $temp['title'] = 'Đăng ký';
        $temp['template'] = 'vusers/signup';
        $this->load->view('layout/layout', $temp);
    }

    public function profile() {
        if ($this->session->userdata('user') == '') {
            redirect('home/chome');
        } //neu chua dang nhap thi quay lai trang chu

        $temp['info'] = $this->Mlog->log(); //hien thi nut dang nhap hoac ten nguoi dung tren header
        $userid = $temp['info']['userID'];
        //sua thong tin ca nhan
        if ($this->input->post('save_info')) {
            $lastname = $this->input->post('last_name');
            $firstname = $this->input->post('first_name');
            $month = $this->input->post('month');
            $day = $this->input->post('day');
            $year = $this->input->post('year');
            $birthday = $this->input->post('birthday');
            $gender = $this->input->post('gender');
            $phone = $this->input->post('phone');
            $province = $this->input->post('province');
            $addr = $this->input->post('address');
            $this->Musers->updateProfile($userid, $firstname, $lastname, $birthday, $gender, $phone, $addr, $province, $temp['info']['email']);
        }
        //thay doi mat khau
        
        
        //lay thong tin san pham va phan trang
        $display = 2; //so ban ghi moi trang
        $start = 0; //vi tri mac dinh khi load trang
        $page = 1; //trang mac dinh khi load

        if (isset($_GET['page']) && (int) $_GET['page'] > 0)
            $page = $_GET['page'];
        $record = count($this->Musers->getProductByUID($userid)); //tong so ban ghi
        if ($record > $display)//neu ban ghi nho hon quy dinh thi khong can phan trang
            $num_page = ceil($record / $display); //tong so trang
        else
            $num_page = 1;
        $start = (isset($_GET['page']) && (int) $_GET['page'] > 0) ? ($_GET['page'] - 1) * $display : 0; //nhan bien truyen vao tu url
        $temp['product'] = $this->Musers->getProductByUID($userid, $display, $start);
        $temp['paging'] = array('num_page' => $num_page, 'page' => $page, 'start' => $start, 'display' => $display);
        ////////////////////

        $temp['profile'] = $this->Musers->getProfile($temp['info']['userID']);
        $temp['title'] = 'Thông tin cá nhân';
        $temp['template'] = 'vusers/profile';
        $this->load->view('layout/layout', $temp);
    }

    public function sendMail($email, $psw, $name) {
        $key = $this->encrypt->encode($email);
        $link_active = base_url() . "index.php/home/cusers/active/?key=" . urlencode($key);
        $message = "Xin chào " . $name . ".</br>
            Bạn đã đăng ký tài khoản thành công</br>
            Vui lòng click vào link bên dưới để kích hoạt tài khoản và sử dụng dịch vụ! <br/>
            You has been register completed ! <br/>
            Please check your email address to active your account and use system !</br>
            Link : <a href=" . $link_active . ">" . $link_active . "</a><br/> password :" . $psw;
        $mail = array(
            "to_receiver" => $email,
            "message" => $message,
        );

        $this->load->library('my_email');
        $this->my_email->config($mail);
        $this->my_email->sendmail();
    }

    public function active() {
        if (isset($_GET['key'])) {
            $email = $this->encrypt->decode($_GET['key']); //gia ma key nhan tu url
            if ($this->checkEmail(urldecode($email)) === TRUE) {//neu ma trung voi email thi tien hanh
                $data = array(
                    'status' => 1);
                $this->db->where("email", "$email");
                $this->db->update('user', $data);
                $ck = $this->db->affected_rows(); //kiem tra co bao nhieu ban ghi nao duoc chinh sua
                $temp['template'] = 'vusers/notice';
                if ($ck > 0) {
                    $temp['content'] = '<h1>Tài khoản của bạn đã được đăng ký và kích hoạt thành công với email: ' . $email . '</h1>
                <p>Xin chân thành cảm ơn</p>
                <a href="' . base_url() . 'index.php/home/chome">Quay về trang chủ</a>';
                    $this->load->view('layout/layout', $temp);
                } else {
                    $temp['content'] = '<h1>Tài khoản này đã được kích hoạt với email: ' . $email . '</h1>
                <a href="' . base_url() . 'index.php/home/chome">Quay về trang chủ</a>';
                    $this->load->view('layout/layout', $temp);
                }
            } else {
                $temp['content'] = '<h1>Quá trình kích hoạt thất bại</h1>
                <p>Vui lòng kiểm tra email hoặc đăng nhập để gửi lại key kích hoạt</p>
                <a href="' . base_url() . 'index.php/home/chome">Quay về trang chủ</a>';
                $temp['template'] = 'vusers/notice';
                $this->load->view('layout/layout', $temp);
            }
        }
    }

    public function checkEmail($email) {
        $query = $this->db->select('email')->from('user')->where('email', $email)->get()->row_array();
        if ($query && count($query))
            return TRUE;
        else
            return FALSE;
    }

    public function vd($UID) {
        $temp['title'] = 'Chi tiết sản phẩm';
        $temp['template'] = 'vd';
        $temp['data_detail'] = $this->Musers->getProductByUID($UID);
        $this->load->view('layout/layout', $temp);
    }

}