<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Auth extends CI_Controller
{
    public function __construct()
    {
        //Memanggil method __construct pada CI_Controller
        parent::__construct();

        //Load Form Validation
        $this->load->library('form_validation');
    }

    public function index()
    {
        // $this->default();
        // if ($this->session->userdata('email')) {
        //     redirect('user');
        // }

        if ($this->session->userdata('role_id') == 1) {
            redirect('admin');
        } else if ($this->session->userdata('role_id') == 2) {
            redirect('user');
        }
        //Set Rules
        $this->form_validation->set_rules('email', 'Email', 'required|trim|valid_email');
        $this->form_validation->set_rules('password', 'Password', 'required|trim');

        //Form Validation
        if ($this->form_validation->run() == false) {
            $data['title'] = 'Login';
            $this->load->view('templates/auth_header', $data);
            $this->load->view('auth/login');
            $this->load->view('templates/auth_footer');
        } else {
            //Ketika validasi berhasil
            $this->_login();
        }
    }

    // public function default()
    // {
    // }

    //Membuat method _Login & method harus diprivate
    private function _login()
    {
        //Mengambil data setelah Login
        $email = $this->input->post('email');
        $password = $this->input->post('password');

        //Query data ke Database
        $user = $this->db->get_where('user', ['email' => $email])->row_array(); //Select * From 'table' where email = email

        //Jika ada User / User active
        if ($user) {
            //Jika User active
            if ($user['is_active'] == 1) {
                //Cek password using PHP
                //Pakai function password_verify
                if (password_verify($password, $user['password'])) { //Mencocokkan password
                    //Siapkan data didalam session
                    $data = [
                        'email' => $user['email'],
                        'role_id' => $user['role_id']
                    ];

                    //Data disimpan ke dalam session
                    $this->session->set_userdata($data);
                    if ($user['role_id'] == 1) {
                        redirect('admin');
                    } else
                        redirect('user');
                    // $this->default();
                } else {
                    //Password salah / wrong password
                    $this->session->set_flashdata('message', '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                    Wrong password!
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                    </button>
                    </div>'); //item : nama bebas , value : nilai / isi pesan(session)
                    redirect('auth');
                }
            } else {
                $this->session->set_flashdata('message', '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                Failed :( This email has not been activated!
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
                </button>
                </div>'); //item : nama bebas , value : nilai / isi pesan(session)
                redirect('auth');
            }
        } else {
            //Login gagal & kasih pesan error
            $this->session->set_flashdata('message', '<div class="alert alert-danger alert-dismissible fade show" role="alert">
            Failed :( Email is not registered!
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
            </button>
            </div>'); //item : nama bebas , value : nilai / isi pesan(session)
            redirect('auth');
        }
    }

    public function logout()
    {
        //tombol logout
        $this->session->unset_userdata('email');
        $this->session->unset_userdata('role_id');

        $this->session->set_flashdata('message', '<div class="alert alert-success alert-dismissible fade show" role="alert">
        Thank you! You have been logout.
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
        </button>
        </div>'); //item : nama bebas , value : nilai / isi pesan(session)
        redirect('auth');
    }

    public function blocked()
    {
        // $this->default();
        $this->load->view('auth/blocked');
    }

    //Mengirim email untuk Aktivasi email
    private function _sendEmail($token, $type)
    {
        //Konfigurasi email/SMTP
        $config = [
            'protocol' => 'smtp',
            'smtp_host' => 'ssl://smtp.googlemail.com',
            'smtp_user' => 'dwiky.amin@gmail.com',
            'smtp_pass' => 'dwiky456',
            'smtp_port' => 465,
            'mailtype' => 'html',
            'charset' => 'utf-8',
            'newline' => "\r\n"
        ];


        $this->load->library('email', $config);
        $this->email->initialize($config);  //tambahkan baris ini

        $this->email->from('dwiky.amin@gmail.com', 'Dwiky'); //Email dikirim dari smtp_user
        $this->email->to($this->input->post('email'));

        if ($type == 'verify') {
            $this->email->subject('Account Verification');
            $this->email->message('Click link to verify your account : 
                <a href="' . base_url() . 'auth/verify?email=' . $this->input->post('email') . '&token=' . urlencode($token) . '"> Activate! </a>');
        }

        if ($this->email->send()) {
            return true;
        } else {
            echo $this->email->print_debugger();
            die;
        }
    }

    public function verify()
    {
        $email = $this->input->get('email');
        $token = $this->input->get('token');

        //Cek Valid Email
        //Query user
        $user = $this->db->get_where('user', ['email' => $email])->row_array();

        if ($user) {
            $user_token = $this->db->get_where('user_token', ['token' => $token])->row_array();

            //cek token apakah benar / tidak
            if ($user_token) {
                if (time() - $user_token['date_created'] < (60 * 60 * 24)) {
                    $this->db->set('is_active', 1);
                    $this->db->where('email', $email);
                    $this->db->update('user');

                    $this->db->delete('user_token', ['email' => $email]);

                    //Silahkan Login
                    $this->session->set_flashdata('message', '<div class="alert alert-success alert-dismissible fade show" role="alert">
                    ' . $email . ' has been activated. Please login!
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                    </button>
                    </div>'); //item : nama bebas , value : nilai / isi pesan(session)
                    redirect('auth');
                } else {
                    //Hapus email
                    $this->db->delete('user', ['email' => $email]);
                    //Hapus token
                    $this->db->delete('user_token', ['email' => $email]);

                    $this->session->set_flashdata('message', '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                    Activation account failed! Token Expired:(
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                    </button>
                    </div>'); //item : nama bebas , value : nilai / isi pesan(session)
                    redirect('auth');
                }
            } else {
                $this->session->set_flashdata('message', '<div class="alert alert-danger alert-dismissible fade show" role="alert">
            Activation account failed! Wrong token:(
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
            </button>
            </div>'); //item : nama bebas , value : nilai / isi pesan(session)
                redirect('auth');
            }
        } else {
            $this->session->set_flashdata('message', '<div class="alert alert-danger alert-dismissible fade show" role="alert">
        Activation account failed! Wrong email:(
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
        </button>
        </div>'); //item : nama bebas , value : nilai / isi pesan(session)
            redirect('auth');
        }
    }

    public function register()
    {
        // $this->default();
        // if ($this->session->userdata('email')) {
        //     redirect('user');
        // }
        if ($this->session->userdata('role_id') == 1) {
            redirect('admin');
        } else if ($this->session->userdata('role_id') == 2) {
            redirect('user');
        }
        //Set rules / Jika form tidak di isi
        $this->form_validation->set_rules('name', 'Name', 'required|trim');
        $this->form_validation->set_rules('email', 'Email', 'required|trim|valid_email|is_unique[user.email]', [
            'is_unique' => 'This email has been registered!'
        ]); //is_unique[table.field]
        $this->form_validation->set_rules('password1', 'Password', 'required|trim|min_length[8]|matches[password2]', [
            'matches' => "password don't match!",
            'min_length' => "password is too short"
        ]);
        $this->form_validation->set_rules('password2', 'Password', 'required|trim|matches[password1]');

        //Form Validation
        if ($this->form_validation->run() == FALSE) {
            $data['title'] = 'Register';
            $this->load->view('templates/auth_header', $data);
            $this->load->view('auth/register');
            $this->load->view('templates/auth_footer');
        } else {
            $email = $this->input->post('email', true);
            //Buat data untuk Insert data ke DB
            $data = [
                'name' => htmlspecialchars($this->input->post('name', true)), //True -> Menghindari Cross-site Scripting
                'email' => htmlspecialchars($email), //True -> Menghindari Cross-site Scripting
                'image' => 'default.jpg',
                'password' => password_hash($this->input->post('password1'), PASSWORD_DEFAULT),
                'role_id' => 2,
                'is_active' => 0,
                'date_created' => time()
            ];

            //Base64_encode => agar mudah dibaca tokennya
            //SIAPKAN TOKEN
            $token = base64_encode(random_bytes(32)); //Dibungkus menggunakan Base64_encode

            //Membuat tabel User Token
            $user_token = [
                'email' => $email,
                'token' => $token,
                'date_created' => time()
            ];

            $this->db->insert('user', $data); //Insert Data
            $this->db->insert('user_token', $user_token); //Insert Data

            $this->_sendEmail($token, 'verify');

            $this->session->set_flashdata('message', '<div class="alert alert-success alert-dismissible fade show" role="alert">
            Congratulation! Please activate your account:)
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
                </button>
                </div>'); //item : nama bebas , value : nilai / isi pesan(session)
            redirect('auth');
        }
    }
}
