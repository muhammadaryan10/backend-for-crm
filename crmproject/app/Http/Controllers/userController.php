<?php

namespace App\Http\Controllers;

use App\Models\Emp_logs;
use App\Models\Renewals_remarks;
use App\Models\Technicaldetails;
use App\Models\User;
use App\Models\Notification;
use App\Models\secutitydetails;
use App\Models\complain;
use App\Models\Deviceinventory;
use App\Models\Complain_actions;
use App\Models\Redo;
use App\Models\Removal;
use App\Models\Transfer;
use App\Models\Old_owner;
use App\Models\Datalogs;
use App\Models\Employee;
use App\Models\Attendance;
use App\Models\Emp_login;
use App\Models\Emp_logout;
use App\Models\Inventory_logs;
use App\Models\Renewals;
use App\Models\SMS;
use GuzzleHttp\Client;



use Illuminate\Http\Response;


use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

use Illuminate\Support\Facades\Validator;
use App\Notifications\UserNotification;
use Illuminate\Http\Request;
use DB;
// use Auth;
use Illuminate\Support\Facades\Hash;
use DateTime;
use PDF;
// use Illuminate\Support\Facades\Auth;

class userController extends Controller
{
    //

    public function view(){
        return view('form');
    }

    public function logout(Request $request)
    {


        $sessionToken = $request->session()->get('session_token');
        $empId = $request->session()->get('emp_id_' . $sessionToken);

        if ($sessionToken !== null && $empId !== null) {
            // Update Emp_login status to 'inactive'
            Emp_login::where('emp_id', $empId)->update(['status' => 'inactive']);

            // Store logout details
            $empName = $request->session()->get('emp_name_' . $sessionToken);
            $logoutId = $request->session()->get('em_loginid_' . $sessionToken);
            $loginId = $request->session()->get('id');
            Emp_logout::create([
                'emp_id' => $empId,
                'Emp_name' => $empName,
                'logout_id' => $logoutId,
                'login_id' => $loginId,
            ]);

            // Remove ONLY the specific session data for this employee's session
            $request->session()->forget([
                'emp_id_' . $sessionToken,
                'emp_name_' . $sessionToken,
                'em_loginid_' . $sessionToken,
                // ...other relevant session keys
            ]);

            return redirect('/login');
        }

        \Log::error('Invalid or missing session data during logout', [
            'sessionToken' => $sessionToken,
            'empId' => $empId,
            // ...other relevant session data for debugging
        ]);

        return view('RefreshError')->with('message', 'you have already loggedout');
    }



    public function user(){
        // Fetch data from the database using your User model
        // Get the last complaint ID
        $lastComplaint = User::latest()->first();
        $lastComplaintId = $lastComplaint ? $lastComplaint->id + 1 : 1;
        // $data = User::pluck('client_id', 'client_id');

        // return view('userdetails', compact('lastComplaintId'));
        return response()->json([
            'succes'=>true,
            'message'=>'successfully',
            'client_id'=>$lastComplaintId
        ], 200, );
    }


    public function storedata(Request $request)
    {
        
        $validatedData = $request->validate([
            'id' => 'required',
            'customer_name' => 'required',
            'father_name' => 'required',
            'address' => 'required',
            'telephone_residence' => 'required|numeric', // Added numeric validation
            'mobileno_1' => 'required',
            'mobileno_2' => 'nullable',
            'mobileno_3' => 'nullable',
            'mobileno_4' => 'nullable',
            'ntn' => 'required|unique:users,ntn',
            'cnic' => 'required|unique:users,cnic', // Corrected unique validation
            'seconadryuser_name' => 'required', // Corrected spelling
            'secondaryuser_con1' => 'required',
            'secondaryuser_con2' => 'nullable',
            'relationship' => 'required',
            'registeration_no' => 'required|unique:users,registeration_no',
            'chasis_no' => 'required',
            'engine_no' => 'required',
            'engine_type' => 'required',
            'CC' => 'required',
            'make' => 'required',
            'model' => 'required',
            'year' => 'required|numeric',
            'color' => 'required',
            'insurance_partner' => 'required',
            'vas' => 'required', // Assuming this is a required field
            'vas_options' => 'nullable',
            'segment' => 'nullable',
            'demo_duration' => 'required', // Assuming this is a required field
            'tracker_charges' => 'required',
            'date_of_installation' => 'required',
            'int_comission' => 'required',
            'ext_comission' => 'required',
            'discount' => 'required',
            'campaign_point' => 'required',
            'dealership' => 'required',
            'dealer_name' => 'required',
            'sales_person' => 'required',
            'installation_loc' => 'required',
            'conatct_person' => 'required',
            'remarks' => 'required',
            'renewal_charges' => 'required',
            'primaryuser_name'=>'required',
            'primaryuser_con1'=>'required',
            'primaryuser_cnic'=>'required',
            'transmission'=>'required',
            'segment'=>'required'

        ]);
    
        try {
            DB::beginTransaction();
    
            $data = new User();
            $data->fill($validatedData); // Fill the model with validated data
    
            // Other fields
            $data->form_status = 'declined';
            $data->save();
    
            $createdDate = $data->created_at instanceof Carbon ? $data->created_at : new Carbon($data->created_at);
            $renewalDate = $createdDate->addYear();
    
            $renewals = Renewals::create([
                'client_id' => $data->id,
                // 'login_id' => $request->cookie('em_loginid'), // Assuming this is the correct way to get empId
                'renewal_charges' => $request->renewal_charges,
                'reg_no' => $request->registeration_no,
                'customer_name' => $request->customer_name,
                'renewal_status' => 'unpaid',
                'renewal_date' => $renewalDate,
            ]);
    
            DB::commit();
    
            return response()->json([
                'success' => true,
                'message' => 'Data submitted successfully',
                'users-data'=>$data,
            ], 200);
        } catch (\Exception $e) {
            $error= $e->getMessage();
            DB::rollback();
            return response()->json(['message' => 'Internal server error',
        'error'=>$error,], 420);
        }
    }
    
    
    public function show(){
              $data = User::orderBy('created_at','desc')
              ->paginate(20);

              $count = User::count(); // Replace 'your_table_name' with the actual table name
              return view('alluser',compact('data','count'));
           }
    //        public function record(){
    //         $data = User::all();
    //        $count= User::count();
    //   return view('alluser' ,compact('data','count'));
    //      }
    public function delete($id){
        User::destroy($id);
        return back();
    }
    public function edit($reg_no){
        // $validator=Validator::make($request->all(),[
        //     'reg_no'=>'required'
        // ]);
        // if($validator->fails()){
        //     return response()->json([
        //         'success'=>false,
        //         'message'=>$validator->errors()
        //     ], 200, );
        // }
        $data = User::where('registeration_no',$reg_no)
        ->first();
        $data_1 = Technicaldetails::where('client_code', $data->id)->first();
        $data_2 = secutitydetails::where('client_code', $data->id)->first();
        $vas = explode(', ', $data->vas_options);

   return response()->json([
    'success'=>true,
    'message'=>'Data found successfully',
    'user'=>$data,
    'technical'=>$data_1,
    'security'=>$data_2,
    'vas'=>$vas

   ], 200, );
    }

    public function single($id){
        $data= User::find($id);
        $technical = Technicaldetails::where('client_code', $id)->get();
        $security  =secutitydetails::where('client_code',$id)->get();
        $status=Technicaldetails::where('client_code',$id)->select('tracker_status')->first();
        return view('singleuser',compact('data','technical','security','status'));
      }
      public function technicalregisteration(){
        return view('technicalregisteration');
      }
      public function securityalert(){
        $data =User::where('form_status','pending')->get();
        return view('securityalert',compact('data'));
      }

      public function alert()
      {
           $data =User::where('form_status','declined')->get(); // Replace 'Notification' with your model name.
            // return ($data);
         return view('alert', ['data' => $data]);
        }
        public function furtherdetails($id){
            $data=User::find($id);
            $vas = explode(',', $data->vas_options);
             // Assuming options are stored as a comma-separated string
            return view('furtherdetails',compact('data','vas'));
            // return($data);
          }
          public function securityform($id){
            $data=User::find($id);
            $vas = explode(', ', $data->vas_options);
            $data_1=Technicaldetails::where('client_code',$id)->first();
            return view('securitybriefing',compact('data','vas','data_1'));
            // return($data);
          }


  public function technical_details(Request $request){
    // $validator = Validator::make($request->all(), [
    //     'vendor_name' => 'required',
    //     'device_id' => 'required',
    //     'IMEI_no' => 'required',
    //     'Gsm_no' => 'required',
    //     'Tavl_mang_id' => 'required',
    //     'technician_name' => 'required',
    //     'sim' => 'required',
    //     'Gps_check' => 'required',
    //     'mobilizer' => 'required',
    //     'operational_status' => 'required',
    //     'webtrack_id' => 'required',
    //     'webtrack_pass' => 'required|min:6', // Example: minimum length of 6 characters
    //     'ignition_alerts' => 'required',
    //     'overspeed_alerts' => 'required',
    //     'geo_fence_alerts' => 'required',
    //     'additional_contact' => 'required',
    //     'contact_1' => 'required',
    //     'contact_2' => 'required',
    //     'contact_3' => 'required',
    // ]);

    // if ($validator->fails()) {
    //     return back()->withErrors($validator)->withInput();
    // }

  $value= new Technicaldetails();
  $client_value = User::where('id', $request->client_code)->first();
$device=Deviceinventory::where('device_serialno',$request->input('device_id'))->select('id')->first();
 $device->update(['status' => 'inactive']);
if ($client_value && $device) {
    $value->client_code = $client_value->id;
    $value->device_no=$device->id;

}
  $value->vendor_name=$request->input('vendor_name');
  $value->device_id=$request->input('device_id');
  $value->IMEI_no=$request->input('IMEI_no');
  $value->Gsm_no=$request->input('Gsm_no');
  $value->Tavl_mang_id=$request->input('Tavl_mang_id');
  $value->technician_name=$request->input('technician_name');
  $value->sim=$request->input('sim');
  $value->Gps_check=$request->input('Gps_check');
  $value->mobilizer=$request->input('mobilizer');
  $value->operational_status=$request->input('operational_status');
  $value->webtrack_id=$request->input('webtrack_id');
  $value->webtrack_pass=Hash::make($request->input('webtrack_pass'));
  $value->ignition_alerts=$request->input('ignition_alerts');
  $value->overspeed_alerts=$request->input('overspeed_alerts');
  $value->geo_fence_alerts=$request->input('geo_fence_alerts');
  $value->additional_contact=$request->input('additional_contact');
  $value->contact_1=$request->input('contact_1');
  $value->contact_2=$request->input('contact_2');
  $value->contact_3=$request->input('contact_3');
  $value->tracker_status='active';
  $value->save();
  if($value){
    User::where('id', $value->client_code)->update(['form_status'=>'pending']);
  }

  return redirect()->route('alert');
  }
  public function securitydetails(Request $request){
    $value= new secutitydetails();
    $client_value=User::where('id',$request->client_code)->first();
    if($client_value){
        $value->client_code=$client_value->id;
    }
    $value->customer_email=$request->input('customer_email');
    $value->emergency_pass=$request->input('emergency_pass');
    $value->emergency_person=$request->input('emergency_person');
    $value->security_ques=$request->input('security_ques');
    $value->security_ans=$request->input('security_ans');
    $value->password=Hash::make($request->input('password'));
    $value->emergency_person_contact=$request->input('emergency_person_contact');
    $value->save();
    if($value){
        User::where('id',$value->client_code)->update(['form_status'=>'approved']);
    }
    return redirect()->route('securityalert');



  }
  public function update_details(Request $request,$id){
    $customer=User::find($id);
     if($customer){
        $customer->id=$request->input('client_id');
        $customer->customer_name=$request->input('customer_name');
        $customer->father_name=$request->input('father_name');
        $customer->address=$request->input('address');
        $customer->telephone_residence=$request->input('telephone_residence');
        $customer->mobileno_1=$request->input('mobileno_1');
        $customer->mobileno_2=$request->input('mobileno_2');
        $customer->mobileno_3=$request->input('mobileno_3');
        $customer->mobileno_4=$request->input('mobileno_4');
        $customer->ntn=$request->input('ntn');
        $customer->cnic=$request->input('cnic');
        $customer->primaryuser_name=$request->input('primaryuser_name');
        $customer->primaryuser_con1=$request->input('primaryuser_con1');
        $customer->primaryuser_con2=$request->input('primaryuser_con2');
        $customer->primaryuser_cnic=$request->input('primaryuser_cnic');
        $customer->seconadryuser_name=$request->input('seconadryuser_name');
        $customer->secondaryuser_con1=$request->input('secondaryuser_con1');
        $customer->secondaryuser_con2=$request->input('secondaryuser_con2');
        $customer->relationship=$request->input('relationship');
        // $customer->registeration_no=$request->input('registeration_no');
        // $customer->chasis_no=$request->input('chasis_no');
        // $customer->engine_no=$request->input('engine_no');
        // $customer->engine_type=$request->input('engine_type');
        // $customer->CC=$request->input('CC');
        // $customer->make=$request->input('make');
        // $customer->model=$request->input('model');
        // $customer->year=$request->input('year');
        // $customer->transmission=$request->input('transmission');
        // $customer->color=$request->input('color');
        $customer->insurance_partner=$request->input('insurance_partner');

        $customer->vas=$request->input('vas');
        $vasOptions = $request->input('vas_options', []);
        $customer->vas_options = implode(',', $vasOptions);

        $customer->segment=$request->input('segment');
        $customer->demo_duration=$request->input('demo_duration');
        $customer->tracker_charges=$request->input('tracker_charges');
        $customer->date_of_installation=$request->input('date_of_installation');
        $customer->int_comission=$request->input('int_comission');
        $customer->ext_comission=$request->input('ext_comission');
        $customer->discount=$request->input('discount');
        $customer->campaign_point=$request->input('campaign_point');
        $customer->dealership=$request->input('dealership');
        $customer->dealer_name=$request->input('dealer_name');
        $customer->sales_person=$request->input('sales_person');
        $customer->installation_loc=$request->input('installation_loc');
        $customer->conatct_person=$request->input('conatct_person');
        $customer->remarks=$request->input('remarks');
        $customer->update();

    }
    $technical=Technicaldetails::where('client_code',$id)->first();
    // $device=Deviceinventory::where('device_serialno',$request->input('device_id'))->select('id')->first();
    if($technical){
        // $technical->device_no=$device->id;
        $technical->vendor_name=$request->input('vendor_name');
        $technical->device_id=$request->input('device_id');
        $technical->IMEI_no=$request->input('IMEI_no');
        $technical->Gsm_no=$request->input('Gsm_no');
        $technical->Tavl_mang_id=$request->input('Tavl_mang_id');
        $technical->technician_name=$request->input('technician_name');
        $technical->sim=$request->input('sim');
        $technical->Gps_check=$request->input('Gps_check');
        $technical->mobilizer=$request->input('mobilizer');
        $technical->operational_status=$request->input('operational_status');
        $technical->webtrack_id=$request->input('webtrack_id');
        $technical->webtrack_pass=Hash::make($request->input('webtrack_pass'));
        $technical->ignition_alerts=$request->input('ignition_alerts');
        $technical->overspeed_alerts=$request->input('overspeed_alerts');
        $technical->geo_fence_alerts=$request->input('geo_fence_alerts');
        $technical->additional_contact=$request->input('additional_contact');
        $technical->contact_1=$request->input('contact_1');
        $technical->contact_2=$request->input('contact_2');
        $technical->contact_3=$request->input('contact_3');
        $technical->tracker_status='active';
        $technical->update();
        $device=Deviceinventory::where('device_serialno',$technical->device_id)->update(['status'=>'inactive']);
    }
    $security=secutitydetails::where('client_code',$id)->first();
    if($security){
        $security->customer_email=$request->input('customer_email');
        $security->emergency_pass=$request->input('emergency_pass');
        $security->emergency_person=$request->input('emergency_person');
        $security->security_ques=$request->input('security_ques');
        $security->security_ans=$request->input('security_ans');
        $security->password=Hash::make($request->input('password'));
        $security->emergency_person_contact=$request->input('emergency_person_contact');
        $security->update();
    }
    // $customer->update();
    // $technical->update();
    // $security->update();
    return redirect()->route('show');
}
    public function complainform(){

        $lastComplaint = complain::latest()->first();
        $lastComplaintId = $lastComplaint ? $lastComplaint->complain_id + 1 : 1;
        return view('complainform',compact('lastComplaintId'));
    }
    public function create_complain(Request $request){

        $validator = Validator::make($request->all(), [
            'client_id'=>'required',
            'customer_name' => 'required',
            'reg_no' => 'required',
            'nature_of_complain' => 'required',
            'last_location' => 'nullable',
            'Date' => 'nullable',
            'Time' => 'nullable',
            'remarks' => 'required',


        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }


        $sessionToken = $request->session()->get('session_token');
        $empId = $request->session()->get('em_loginid_' . $sessionToken);
        if ($empId) {
            $empid = Employee::where('em_loginid', $empId)
            ->select('emp_name', 'emp_id', 'designation')
            ->first();





        $data = new complain();
        $data->client_id = $request->input('client_id');
        $data->customer_name = $request->input('customer_name');
        $data->reg_no = $request->input('reg_no');

        // Check if "Your Opinion" was selected and set the value accordingly
        if ($request->input('nature_of_complain') == "Your Opinion") {
            $data->nature_of_complain = $request->input('custom_nature');
        } else {
            $data->nature_of_complain = $request->input('nature_of_complain');
        }
        $data->last_location = $request->input('last_location');
        $data->Date = $request->input('Date');
        $data->Time = $request->input('Time');
        $data->remarks = $request->input('remarks');
        $data->Status = 'Pending';

        $data->emp_id = $empid->emp_id;
        $data->emp_login_id = $empId;
        $data->emp_name =  $empid->emp_name;





        $data->save();



        if($data->nature_of_complain=='N/R(no report)'){
          $technical=  Technicaldetails::where('client_code',$data->client_id)->update(['tracker_status'=>'inactive']);

        }

        return response()->json([
            'success' => true,
            'data' => 'complain created successfully',
        ], 200);    }
}

    public function removalForm(Request $request){


        $sessionToken = $request->session()->get('session_token');
        $empId = $request->session()->get('em_loginid_' . $sessionToken);
        $empid = Employee::where('em_loginid', $request->em_loginid)
        ->select('emp_name', 'emp_id','designation','role')
        ->first();
        $lastComplaint = Removal::latest()->first();
        $lastComplaintId = $lastComplaint ? $lastComplaint->id + 1 : 1;

        // Return the second view
        return view('removal', compact('lastComplaintId', 'empid'));
    }
    public function create_removal(Request $request){
     $validator=Validator::make($request->all(),[
        'id'=>'required',
        'reg_no' => 'required|exists:users,registeration_no',
        'client_id' => 'required',
        'customer_name' => 'required',
        'sales_per' => 'required',
        'make' => 'required',
        'model' => 'required',
        'color' => 'required',
        'device' => 'required',
        'eng_no' => 'required',
        'chasis' => 'required',
        'contact_no' => 'required',
        'install_loc' => 'required',
        'install_date' => 'required|date',
        'remarks' => 'required',
     ]);

     if($validator->fails()){
        return response()->json([
            'success'=>false,
            'message'=>'validations error',
            'error'=>$validator->errors()
        ], 402, );
     }
    $removal= Removal::where('reg_no',$request->reg_no)
    ->first();
    if($removal)
     {
        return response()->json([
            'success'=>false,
            'message' => 'Removal already done',
        'data'=>null]
        , 420);
    }

        $data = new Removal();
        $data->id = $request->input('id');
        $data->client_id = $request->input('client_id');
        $data->customer_name = $request->input('customer_name');
        $data->reg_no = $request->input('reg_no');
        $data->sales_per = $request->input('sales_per');
        // list($make, $model) = explode('/', $request->input('make_model'));
    $data->make = $request->input('make');;
    $data->model = $request->input('model');;
        $data->color = $request->input('color');
        $data->device = $request->input('device');
        $data->eng_no = $request->input('eng_no');
        $data->chasis = $request->input('chasis');
        $data->contact_no = $request->input('contact_no');
        $data->install_loc = $request->input('install_loc');
        $data->install_date = $request->input('install_date');
        $data->remarks = $request->input('remarks');
        $data->status = 'Removed';
        $data->save();
        $check=Deviceinventory::where('device_serialno',$request->device)->first()
        ->update(['status'=>'Removed']);

        return response()->json([
            'success'=>true,
            'message' => 'Removal created successfully',
        'data'=>$data
    ], 200);
    }

    public function action_complain(Request $request){
        $action = new Complain_actions();
        $action->complain_code=$request->input('complain_id');
        $action->actions=$request->input('actions');
        if($request->input('actions')=='resolve'){
            complain::where('complain_id',$request->input('complain_id'))->update(['Status'=>'Resolved']);

        }
        $action->remarks_1=$request->input('remarks_1');
        $action->options=$request->input('options');
        $action->save();
        return back();

    }

    public function allcomplain(){
        $data = complain::orderBy('created_at','desc')->get();
        $count = $data->count();
    
        // Format created_at field according to Karachi timezone
        $data->transform(function ($item) {
            $item['date'] = $item['created_at']->timezone('Asia/Karachi')->format('d_m_Y');
            $item['time'] = $item['created_at']->timezone('Asia/Karachi')->format('h:i A');
            unset($item['created_at']); // Remove original created_at field
            return $item;
        });
    
        return response()->json([
            'success' => true,
            'message' => 'complains fetched successfully',
            'count' => $count,
            'all_complains' => $data,
        ], 200);
    }
    

 public function single_complain($complain_id)
 {
    $data=complain::where('complain_id',$complain_id)->first();
        return view ('complaindetails',compact('data'));
 }

 public function reguserdata(Request $request)
 {
     $search = $request->input('serch_term');

     $user = User::where('registeration_no', $search)
     ->orWhere('customer_name','LIKE',"%$search%")
     ->orderBy('created_at','desc')
     ->first();
    //  $data = $user ? $user->id : null;
     $lastComplaint = complain::latest()->first();
     $lastComplaintId = $lastComplaint ? $lastComplaint->complain_id + 1 : 1;

     if ($user) {
         // If a user is found, you can pass the user data to the view
         return view('complainform', compact('user','lastComplaintId'));
     } else {
         return redirect()->back()->with('error', 'Data not found.');
     }
 }

 public function complainLogs($regNo)
 {
     $complainLogs = complain::where('reg_no', $regNo)
     ->orWhere('customer_name','LIKE',"%$regNo%")
     ->orderBy('created_at','desc')
     ->get();
   // Check if there are any complaints in the collection
   if ($complainLogs->isNotEmpty()) {
    $reg_no = $complainLogs->first()->customer_name;
} else {
    $reg_no = null; // Set a default value if there are no complaints
}     return view('complain_logs', compact('complainLogs','reg_no'));
 }

 public function complian_notification()
 {
     // Get complain codes with 'redo' option from the Complain_actions table
     $complainCodes = Complain_actions::where('options', 'redo')->pluck('complain_code')->toArray();

     // Fetch corresponding customer names from the complain table
     $complainData = complain::whereIn('complain_id', $complainCodes)
     ->where('status','Pending')
     ->pluck('client_id')->toArray();

     $data =User::whereIn('id',$complainData)->get();

     return view('complainnotification', compact('data'));
 }


public function requestform(){
    return view('Requestform');
}

public function create_deviceinventory(Request $request) {

    $sessionToken = $request->session()->get('session_token');
    $empId = $request->session()->get('em_loginid_' . $sessionToken);


    $validator = Validator::make($request->all(), [
        'device_serialno' => 'required',
        'imei_no' => 'required',
        'vendor' => 'required',
        'devciesim_no' => 'required',
    ]);

    if ($validator->fails()) {
        return back()->withErrors($validator)->withInput();
    }

    $device = deviceinventory::where('device_serialno', $request->device_serialno)->first();
    if ($device) {
        return response()->json(['response' => "This device already exists"], 400);
    }

    $data = new deviceinventory();
    $data->device_serialno = $request->input('device_serialno');
    $data->imei_no = $request->input('imei_no');
    $data->vendor = $request->input('vendor');
    $data->devciesim_no = $request->input('devciesim_no');
    $data->status = 'active';
    $data->save();
    if ($empId) {
        $empid = Employee::where('em_loginid', $empId)
        ->select('emp_name', 'emp_id', 'designation')
        ->first();

$inevntory_logs= new Inventory_logs();
$value=[
    'emp_id'=>$empid->emp_id,
    'emp_login_id'=>$empId,
    'emp_name'=>$empid->emp_name,
    'actions'=>'DEVICE INVENTORY FORM',
    'status'=>'done',
    'device_serialno'=>$request->device_serialno,
];
Inventory_logs::create($value);
    if ($data) {
        return response()->json(['response' => "Inventory created successfully"], 200);
    } else {
        return response()->json(['response' => "Might be server error"], 500);
    }
}


// return redirect()->route('reqform');
}

public function installation(){
    return view('installation');
}
public function redo($id){
    $data=User::find($id);
    $value=Technicaldetails::where('client_code',$id)->first();
    if($value){
        $data_1=$value->device_id;


    return view('redo',compact('data','data_1'));
    }
}
// public function redo_create($id){
//     $data = Complain_actions::where('options', 'redo')->select('complain_code')->get();
// return view ('redo',compact('data'));
// }

//  public function redo($id){
//      $data = Complain_actions::where('options', 'redo')->select('complain_code')->get();
//      if($data){
//         foreach($data as $k){
//         $value=$k->complain_code;
//         $data_1=complain::where ('complain_id',$value)->select('client_id')->get();
//         if($data_1){
//             foreach($data_1 as $id){
//                 $cleint_id=$id->client_id;
//                 $value_1=User::where('id',$cleint_id)->get();
//             }
//         }
//         $data_2[]=$value_1;
//         }
//      }
//     //  $data_1=complain::where ('complain_id',$data->complain_code)->get();
// return view('redo',compact('data_2','data_1'));


public function create_redo(Request $request){
    $data= new Redo();
// $redo=$request->input('old_device');
$check = Technicaldetails::where('device_id', $request->old_device)->select('device_no')->first();

if ($check) {
    $deviceInventory = Deviceinventory::where('id', $check->device_no)->first(); // Retrieve the Deviceinventory record
    if ($deviceInventory) {
        $deviceInventory->update(['status' => 'active']); // Update the 'status' attribute
    }
}
$new=Deviceinventory::where('device_serialno',$request->input('new_device'))->first()
->update(['status'=>'inactive']);
$technical=Technicaldetails::where('device_id',$request->input('old_device'))
->update(['device_id'=>$request->new_device]);
    $data->client_id=$request->input('client_id');
    $data->charges=$request->input('charges');
    $data->customer_name=$request->input('customer_name');
    $data->contact_no=$request->input('contact_no');
    $data->reg_no=$request->input('reg_no');
    $data->technician=$request->input('technician');
    $data->old_device=$request->input('old_device');
    $data->new_device=$request->input('new_device');
    $data->eng_no=$request->input('eng_no');
    $data->chasis_no=$request->input('chasis_no');
    $data->install_loc=$request->input('install_loc');
    $data->install_date=$request->input('install_date');
    $data->remarks=$request->input('remarks');
    $data->sales_person=$request->input('sales_person');
    $data->harness_change=$request->input('harness_change');
    $data->backupbattery_change=$request->input('backupbattery_change');
   $data->save();
   if($data){
    $user=User::where('id',$request->input('client_id'))->first();
    $user->status='redodone';
    $user->save();
   }
   if($user){
    $update=complain::where('client_id',$request->input('client_id'))->update(['Status'=>'Resolved']);
    // $complain=$update->complain_id;
    // $value=Complain_actions::where('complain_code',$complain)->update(['Status'=>'Resolved']);


   }
   return redirect()->route('cc');

}
public function create_inventory(Request $request){
    $request->validate([
        'device_serialno' => 'required|unique:deviceinventory',
    ]);

    $data= new Deviceinventory();
    $data->device_serialno=$request->input('device_serialno');
    $data->imei_no=$request->input('imei_no');
    $data->vendor=$request->input('vendor');
    $data->devciesim_no=$request->input('devciesim_no');
    $data->status='active';
    $data->save();
   return redirect()->route('reqform');

}
public function getDeviceSerialNumbers(Request $request)
{
    $searchTerm = $request->input('searchTerm');

    $deviceSerialNumbers = Deviceinventory::where('status','active')->select('device_serialno')
        ->where('device_serialno', 'LIKE', "%$searchTerm%")
        ->get();

    return response()->json($deviceSerialNumbers);
}
public function removal_search(Request $request){
    $validator=validator::make($request->all(),
    [
       'search_term'=>'required'
    ]);
 
    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Validation error',
            'errors' => $validator->errors(),
        ], 422);
    }
    $regNo = $request->search_term;

     $user = User::where('registeration_no', $regNo)
     ->orWhere('customer_name','LIKE',"%$regNo%")
     ->orWhere('mobileno_1','LIKE',"%$regNo%")
     ->orWhere('cnic','LIKE',"%$regNo%")
     ->OrderBy('created_at','desc')
     ->first();
     if (!$user) {
        // return redirect()->back()->with('error', 'Data not found.');
        return response()->json([
            'success'=>false,
            'message'=>'Data not found',
            'data'=>null
        ], 400, );
    }

     $device=Technicaldetails::where('client_code',$user->id)->select('device_id','technician_name')->first();
     $lastComplaint = Removal::latest()->first();
     $lastComplaintId = $lastComplaint ? $lastComplaint->id + 1 : 1;

     if ($user) {
         // If a user is found, you can pass the user data to the view
        //  return view('removal', compact('user','lastComplaintId','device'));
        return response()->json([
            'success'=>true,
            'message'=>'Data found successfully',
            'user'=>$user,
            'removal_id'=>$lastComplaintId,
            'device'=>$device
        ], 200, );
     }
}

public function removal_transfer(Request $request){
    $lastComplaint = Removal::latest()->first();
    // $lastComplaintId = $lastComplaint ? $lastComplaint->id + 1 : 1;

    return view('removaltransfer');
}
public function removalate(Request $request)
{
    $regNo = $request->input('reg_no');

     $user = User::where('registeration_no', $regNo)
     ->first();
     if (!$user) {
        return redirect()->back()->with('error', 'This user desnot exist or might be removed.');
    }
     $removalTransfer=Transfer::where('old_reg',$regNo)->first();
     if($removalTransfer){
        return redirect()->back()->with('error', 'The removal transfer alreday exists');

     }
     $device=Removal::where('client_id',$user->id)->select('device')->first();
     $technical =Technicaldetails::where('client_code',$user->id)->select('mobilizer')->first();

     if ($device && $technical) {

         // If a user is found, you can pass the user data to the view
         return view('removaltransfer', compact('user','device','technical'))->with('message','Found');
     }
     else{
        return redirect()->back()->with('error', 'This device is is not removed yet!');


     }

}
public function create_transfer(Request $request){
    // \Log::info($request->all());

    $data= new Transfer();
    $data->client_id =$request->input('client_id');
    $data->old_reg =$request->input('old_reg');
    $data->old_chasis =$request->input('old_chasis');
    $data->old_eng =$request->input('old_eng');
    $data->old_make =$request->input('old_make');
    $data->old_model =$request->input('old_model');
    $data->old_cc =$request->input('old_cc');
    $data->old_color =$request->input('old_color');
    $data->old_trans =$request->input('old_trans');
    $data->old_mob =$request->input('old_mob');
    $data->old_device =$request->input('old_device');
    $data->new_reg =$request->input('new_reg');
    $data->new_chasis =$request->input('new_chasis');
    $data->new_eng =$request->input('new_eng');
    $data->new_make =$request->input('new_make');
    $data->new_cc =$request->input('new_cc');
    $data->new_color =$request->input('new_color');
    $data->new_trans =$request->input('new_trans');
    $data->new_mob =$request->input('new_mob');
    $data->remarks =$request->input('remarks');
    $data->customer_name =$request->input('customer_name');
    $data->old_year =$request->input('old_year');
    $data->new_year =$request->input('new_year');
    $data->new_model =$request->input('new_model');
    $data->new_device =$request->input('new_device');
    $data->status='transfered';
 $data->save();
 if($data){
    Technicaldetails::where('client_code',$request->client_id)->update(['device_id'=>$data->new_device]);
    Deviceinventory::where('device_serialno',$data->new_device)->update(['status'=>'inactive']);
    $user=User::find($data->client_id);
    if($user){
        $user->registeration_no = $data->new_reg;
        $user->chasis_no = $data->new_chasis;
        $user->engine_no = $data->new_eng;
        $user->make = $data->new_make;
        $user->model = $data->new_model;
        $user->CC = $data->new_cc;
        $user->year = $data->new_year;
        $user->color = $data->new_color;
        $user->update();
    }
    $technical=Technicaldetails::find($data->client_id)->first();
    if($technical){
        $technical->device_id=$data->new_device;
        $technical->mobilizer=$data->new_mob;
        $technical->update();

    }

 }
return response()->json(['messsage'=>'Transfer created successfully'], 200,);}

public function view_owner_form(){
    return view('ownership');
}
public function viewownership(Request $request){
    $searchTerm = $request->input('search_term'); // Assuming 'search_term' is used for both customer name and reg_no

    // Search by registration number
    $user = User::where('registeration_no', $searchTerm)
                ->orWhere('customer_name', 'LIKE', "%$searchTerm%")
                ->OrderBy('created_at','desc')
                ->first();

    if (!$user) {
        return view('ownership')->with('error', 'Data not found.');
    }

    $technical = Technicaldetails::where('client_code', $user->id)
                                ->select('mobilizer', 'device_id')
                                ->first();

    // If a user is found, pass the user data to the view
    return view('ownership', compact('user', 'technical'));
}

public function ownership_create(Request $request){
    $old= new Old_owner();
//old customer
    $old->client_id=$request->input('client_id');
    $old->old_customer=$request->input('old_customer');
    $old->old_father=$request->input('old_father');
    $old->old_telephone=$request->input('old_telephone');
    $old->old_address=$request->input('old_address');
    $old->old_mobileno_1=$request->input('old_mobileno_1');
    $old->old_mobileno_2=$request->input('old_mobileno_2');
    $old->old_mobileno_3=$request->input('old_mobileno_3');
    $old->old_mobileno_4=$request->input('old_mobileno_4');
    $old->old_ntn=$request->input('old_ntn');
    $old->old_cnic=$request->input('old_cnic');
    $old->old_primaryname=$request->input('old_primaryname');
    $old->old_primary_con1=$request->input('old_primary_con1');
    $old->old_primary_con2=$request->input('old_primary_con2');
    $old->old_primary_cnic=$request->input('old_primary_cnic');
    $old->old_seconadry_name=$request->input('old_seconadry_name');
    $old->old_relationship=$request->input('old_relationship');
    $old->old_reg_no=$request->input('old_reg_no');
    $old->old_chasis_no=$request->input('old_chasis_no');
    $old->old_engine_no=$request->input('old_engine_no');
    $old->old_make=$request->input('old_make');
    $old->old_color=$request->input('old_color');
    $old->old_tracker_charges=$request->input('old_tracker_charges');
    $old->old_date=$request->input('old_date');
    $old->old_discount=$request->input('old_discount');
    $old->old_install_loc=$request->input('old_install_loc');
    $old->old_conatct_person=$request->input('old_conatct_person');
    $old->old_remarks=$request->input('old_remarks');
    $old->status='done';
    $old->save();



    return back();
}

public function create_soldout(Request $request){
    $user=$request->input('status');
    if($user){
$status=Technicaldetails::where('client_code',$request->input('cleint_id'))->update(['tracker_status'=>$user]);
    }
if($user=='soldout'){
    $old= new Old_owner();
    //old customer
        $old->client_id=$request->input('client_id');
        $old->old_customer=$request->input('old_customer');
        $old->old_father=$request->input('old_father');
        $old->old_telephone=$request->input('old_telephone');
        $old->old_address=$request->input('old_address');
        $old->old_mobileno_1=$request->input('old_mobileno_1');
        $old->old_mobileno_2=$request->input('old_mobileno_2');
        $old->old_mobileno_3=$request->input('old_mobileno_3');
        $old->old_mobileno_4=$request->input('old_mobileno_4');
        $old->old_ntn=$request->input('old_ntn');
        $old->old_cnic=$request->input('old_cnic');
        $old->old_primaryname=$request->input('old_primaryname');
        $old->old_primary_con1=$request->input('old_primary_con1');
        $old->old_primary_con2=$request->input('old_primary_con2');
        $old->old_primary_cnic=$request->input('old_primary_cnic');
        $old->old_seconadry_name=$request->input('old_seconadry_name');
        $old->old_relationship=$request->input('old_relationship');
        $old->old_reg_no=$request->input('old_reg_no');
        $old->old_chasis_no=$request->input('old_chasis_no');
        $old->old_engine_no=$request->input('old_engine_no');
        $old->old_make=$request->input('old_make');
        $old->old_color=$request->input('old_color');
        $old->old_tracker_charges=$request->input('old_tracker_charges');
        $old->old_date=$request->input('old_date');
        $old->old_discount=$request->input('old_discount');
        $old->old_install_loc=$request->input('old_install_loc');
        $old->old_conatct_person=$request->input('old_conatct_person');
        $old->old_remarks=$request->input('old_remarks');
        $old->old_model=$request->input('old_model');
        $old->old_device=$request->input('old_device');
        $old->old_gsm=$request->input('old_gsm');
        $old->old_imei=$request->input('old_imei');

        $old->status='done';
        $old->save();
}
return back();
}
public function create_datalogs(Request $request)
{
    $sessionToken = $request->session()->get('session_token');
    $empId = $request->session()->get('em_loginid_' . $sessionToken);
    $emp = Employee::where('em_loginid', $empId)->first();

    $nature = $request->input('nature');
    $editable_nature = $request->input('editable_nature'); // Assuming you'll rename the input field for edited nature to 'editable_nature'

    $edited_nature = !empty($editable_nature) ? $editable_nature : $nature;

    $data = [
        'client_id' => $request->input('client_id'),
        'customer_name' => $request->input('customer_name'),
        'nature' => $edited_nature,
        'reg_no' => $request->input('reg_no'),
        'representative' => $emp->emp_name,
        'contact_no' => $request->input('contact_no'),
        'contact_person' => $request->input('contact_person'),
        'remarks' => $request->input('remarks'),
    ];

    Datalogs::create($data);

    return response()->json([
        'success' => true,
        'data' => 'Datalogs created successfully',
    ], 200);
}

public function datalogs($search_term){
    $datalogs = Datalogs::where('reg_no', $search_term)
    ->orWhere('customer_name','LIKE',"%$search_term%")
    ->orderBy('created_at','desc')
    ->get();

   if ($datalogs->isNotEmpty()) {
    $reg_no = $datalogs->first()->customer_name;
} else {
    $reg_no = null; // Set a default value if there are no complaints
}
   return view('datalogs', compact('datalogs','reg_no'));
 }
 public function view_employees(Request $request){
    return view('totalemployees');
 }
 public function add_employees(Request $request){
    $Empid = Employee::latest()->first();
    $Employeeid = $Empid ? $Empid->emp_id + 1 : 1;
    // return view('AddEmployee',compact('Employeeid'));
    return response()->json([
        'success'=>true,
        'Emp_id'=>$Employeeid
    ], 200, );
 }

 public function create_emp(Request $request){
    // Validation rules
    $validator=validator::make($request->all(),[
        'emp_name' => 'required|string|max:255',
        'em_loginid' => 'required|string|max:255|unique:workers',
        'password' => 'required|string|min:6',
        'designation' => 'required|string|max:255',
        'contact' => 'required|string|max:20',
        'cnic' => 'required|string|max:15|unique:workers',
        'role' => 'required|unique:roles,roles',
        'image'=>'required|nullable'
    

    ]);


   
    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Validation error',
            'errors' => $validator->errors(),
        ], 400);
    }

  
    $emp = new Employee();
    $emp->emp_name = $request->emp_name;
    $emp->em_loginid = $request->em_loginid;
    $emp->password = bcrypt($request->password); 
    $emp->designation = $request->designation;
    $emp->contact = $request->contact;
    $emp->cnic = $request->cnic;
    $emp->role = $request->role;

    // Handle image upload
    if ($request->hasFile('image')) {
        $imagePath = $request->file('image')->store('image', 'public');
        $emp->image = 'storage/' . $imagePath;
    }

    $emp->status = 'active';
    $emp->save();
    if($emp){

    return response()->json([
        'success' => true,
        'message' => 'Employee added successfully',
        'data' => $emp, 
    ], 200);
}
else{
    return response()->json([
        'success' => false,
        'message' => 'error',
        'data' => null, 
    ], 200);
}
}

 public function all_emp(){
    $data = Employee::orderBy('created_at','desc')
    ->get();
    $count = Employee::count();
    // return view('allemployee', compact('data', 'count'));
    return response()->json([
        'success'=>true,
        'message'=>'data fetched successfully',
        'data'=>$data,
        'count'=>$count
    ], 200, );
}
public function delete_emp($emp_id){
    // $validator = Validator::make($request->all(), [
    //     'emp_id' => ['required'],

    // ]);


    // if ($validator->fails()) {
    //     return response()->json([
    //         'success' => false,
    //         'message' => $validator->errors(),
    //         'data' => null
    //     ], 400);
    // }
  $emp=  Employee::where('emp_id',$emp_id)->delete();
  if($emp){
   return response()->json([
    'success'=>true,
    'message'=>'Employee deleted successfully'
], 200, );
  }
  else{
    return response()->json([
        'success'=>false,
        'message'=>'Internal  server error'
    ], 200, );
  }
}
public function view_update_emp(){
    return view('UpdateEmployee');
}

public function showImages(){
    $employees = Employee::whereNotNull('image')->get();
    return view('allphotos', compact('employees'));
}

public function viewemp_update(Request $request){
    $regNo = $request->input('login_id');
    $user = Employee::where('em_loginid', $regNo)->first();
    if (!$user) {
       return redirect()->back()->with('error', 'Data not found.');
   }
//    $technical =Technicaldetails::where('client_code',$user->id)->select('mobilizer','device_id')->first();

   if ($user) {
       return view('editemp', compact('user'));
   }
}
public function view_update(Request $request){
 $validator=Validator::make($request->all(),[
    'login_id'=>'required'
 ]);
 if ($validator->fails()) {
    return response()->json([
        'success' => false,
        'message' => 'Validation error',
        'errors' => $validator->errors(),
    ], 400);
}

    
    $emp = Employee::where('em_loginid', $request->login_id)
    ->first();
    if (!$emp) {
    //    return redirect()->back()->with('error', 'Data not found.');
    return response()->json([
        'success'=>false,
        'message'=>'data not found',
        'data'=>null
    ], 200, );
   }

   if ($emp) {
    //    return view('UpdateEmployee', compact('user'));
    return response()->json([
        'success'=>true,
        'message'=>'Employee details found',
        'data'=>$emp
    ], 200, );
   }
}

public function create_update_emp(Request $request){
    $validator = Validator::make($request->all(),[
        'emp_id' => 'required'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Validation error',
            'errors' => $validator->errors(),
        ], 400);
    }

    $emp = Employee::where('emp_id', $request->emp_id)->first();

    if($emp){
        // Update the fields if they are present in the request
        if($request->filled('em_loginid')){
            $emp->em_loginid = $request->input('em_loginid');
        }
        if($request->filled('password')){
            $emp->password = $request->input('password');
        }
        if($request->filled('emp_name')){
            $emp->emp_name = $request->input('emp_name');
        }
        if($request->filled('designation')){
            $emp->designation = $request->input('designation');
        }
        if($request->filled('contact')){
            $emp->contact = $request->input('contact');
        }
        if($request->filled('cnic')){
            $emp->cnic = $request->input('cnic');
        }
        if($request->filled('role')){
            $emp->role = $request->input('role');
        }

        $emp->update();
        return response()->json([
            'success' => true,
            'message' => "Employee updated successfully",
            'data'=>$emp
        ], 200);
    }
    else{
        return response()->json([
            'success' => false,
            'message' => "Employee not found"
        ], 404);
    }
}

public function new_emp(){
    return view('editemp');
}

public function single_emp($emp_id){
    $data= Employee::where('emp_id',$emp_id)->first();
    return view('editemp',compact('data'));
  }
  public function change_pic(Request $request){
    $imageURL = null;

    if($request->hasFile('image')){
        $imagepath = $request->file('image')->store('image', 'public');
        $imageURL = 'storage/' . $imagepath;
    }

    Employee::where('emp_id', $request->emp_id)->update(['image' => $imageURL]);

    return back();
}

public function complete_vehicle_details(Request $request) {
    $data = User::OrderBy('created_at','desc')
    ->get();
    $count = $data->count();

    $status = [];
    foreach ($data as $user) {
        $userStatus = $user->client_code()->select('operational_status', 'tracker_status')->first();
        if ($userStatus) {
            $status[] = $userStatus;
        }
    }

    // return view('completeDetails', compact('data', 'count', 'status'));
    return response()->json([
        'success'=>true,
        'message'=>'details found successfully',
        'data'=>$data,
        'status'=>$status,
        'count'=>$count,
    ], 200, );
}



public function Emp_attendance(Request $request){
    $employees = Employee::all();
    $employeeCount = $employees->count();

    // Fetching login records for all employees
    $loginRecords = Emp_login::whereIn('emp_id', $employees->pluck('emp_id'))
    ->orderBy('created_at','desc')
    ->paginate(20);

    // Fetching logout records using login IDs
    $logoutRecords = Emp_logout::whereIn('login_id', $loginRecords->pluck('id'))->get();

    // Creating a collection to store attendance data
    $attendanceData = collect();
    foreach ($loginRecords as $login) {
        $checkIn = Carbon::parse($login->created_at)->setTimezone('Asia/Karachi');
        $checkOut = null;

        $matchingLogout = $logoutRecords->where('login_id', $login->id)->first();

        if ($matchingLogout) {
            $checkOut = Carbon::parse($matchingLogout->created_at)->setTimezone('Asia/Karachi');
        }

        $diff = $checkOut ? $checkOut->diffInSeconds($checkIn) : null;
        $formattedTime = null;

        if ($diff !== null) {
            if ($diff >= 3600) {
                $formattedTime = $checkIn->diff($checkOut)->format('%hh %im');
            } elseif ($diff >= 60) {
                $formattedTime = $checkIn->diff($checkOut)->format('%im');
            } else {
                $formattedTime = $diff . ' seconds';
            }
        }

        $attendanceData->push([
            'emp_id' => $login->emp_id,
            'check_in' => $checkIn->format('h:i A'),
            'check_out' => $checkOut ? $checkOut->format('h:i A') : null,
            'time_worked' => $formattedTime,
            'checkin_date' => $checkIn->format('d-m-Y'),
            'checkout_date' => $checkOut ? $checkOut->format('d-m-Y') : null,


        ]);
    }


    // return view('EmpAttendance', compact('employees', 'employeeCount', 'attendanceData'));
    return response()->json([
        'success'=>true,
        'message'=>'data fetched successfully',
        'data'=> $employees,
        'count'=>$employeeCount,
        'attendance'=>$attendanceData,
    ], 200, );
}
public function fetchAttendanceReport(Request $request) {
    $startDate = Carbon::now()->subDays(30)->startOfDay();
    $endDate = Carbon::now()->endOfDay();

    $loginRecords = Emp_login::whereBetween('created_at', [$startDate, $endDate])
    ->orderBy('created_at','desc')
        ->paginate(20); // Paginate with 7 records per page

    // Collect login record IDs
    $loginRecordIds = $loginRecords->pluck('id');

    // Retrieve logout records for the collected login IDs
    $logoutRecords = Emp_logout::whereIn('login_id', $loginRecordIds)->get();

    $attendanceData = collect();

    foreach ($loginRecords as $login) {
        $checkIn = Carbon::parse($login->created_at)->setTimezone('Asia/Karachi');
        $checkOut = null;

        // Find the corresponding logout record for the login
        $matchingLogout = $logoutRecords->where('login_id', $login->id)->first();

        if ($matchingLogout) {
            $checkOut = Carbon::parse($matchingLogout->created_at)->setTimezone('Asia/Karachi');
        }

        $diff = $checkOut ? $checkOut->diffInSeconds($checkIn) : null;
        $formattedTime = null;

        if ($diff !== null) {
            if ($diff >= 3600) {
                $formattedTime = $checkIn->diff($checkOut)->format('%hh %im');
            } elseif ($diff >= 60) {
                $formattedTime = $checkIn->diff($checkOut)->format('%im');
            } else {
                $formattedTime = $diff . ' seconds';
            }
        }

        // Push data to the collection
        $attendanceData->push([
            'Emp_name' => $login->Emp_name,
            'check_in' => $checkIn->format('h:i A'),
            'check_out' => $checkOut ? $checkOut->format('h:i A') : 'Active',
            'time_worked' => $formattedTime,
            'checkin_date' => $checkIn->format('d-m-Y'),
            'checkout_date' => $checkOut ? $checkOut->format('d-m-Y') : null,
        ]);
    }

    return view('Monthly report', compact('attendanceData', 'loginRecords'));
}



public function attendance(Request $request){
    $employeeIds = Employee::pluck('emp_id');

    $attendanceData = collect(); // Initialize an empty collection

    if($employeeIds->isNotEmpty()){
        $attendanceData = Attendance::whereIn('emp_id', $employeeIds)->get();
    }

    $diff = $attendanceData->map(function ($attendance) {
        $checkIn = strtotime($attendance->check_in);
        $checkOut = strtotime($attendance->check_out);

        // Calculate the difference in seconds
        $timeDiff = $checkOut - $checkIn;

        // Convert seconds to hours and minutes
        $hours = floor($timeDiff / 3600);
        $minutes = floor(($timeDiff % 3600) / 60);

        return [
            'check_in' => $attendance->check_in,
            'check_out' => $attendance->check_out,
            'time_diff' => $hours.' hours '.$minutes.' minutes',
        ];
    });

    return $diff;
    // return view('EmpAttendance', compact('attendanceData')); // Ensure the variable name passed is 'attendanceData'
}

    // return view('EmpAttendance', compact('attendanceData')); // Ensure the variable name passed is 'attendanceData'






public function sales_lead(Request $request){
return view('salesLead');

}



public function emp_login(Request $request)
{
    $validator = Validator::make($request->all(), [
        'em_loginid' => ['required'],
        'password' => ['required'],
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => $validator->errors(),
        ], 400);
    }

    $employee = Employee::where('em_loginid', $request->em_loginid)->first();

    if (!$employee || $request->password != $employee->password) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid Credentials',
        ], 401);
    }

    $empId = $employee->emp_id;
    $empname = $employee->emp_name;
    $loginid = $employee->em_loginid;
    $empRole = $employee->role;
    $designation = $employee->designation;

    // Generate a unique session token
    $uniqueSessionToken = 'session_token_' . $empId . '_' . time();
    $token = $employee->createToken('AuthToken')->plainTextToken;
    // Store employee details with the session token in cookies
    $response = response()->json([
        'success' => true,
        'message' => 'Login successful',
        'emp_id' => $empId,
        'emp_name' => $empname,
        'em_loginid' => $loginid,
        'role' => $empRole,
        'designation' => $designation,
        'token'=>$token
    ]);

    $response->withCookie(cookie('session_token', $uniqueSessionToken, 60, null, null, false, true)); 

    // Create a new Emp_login record
    $emp_login = Emp_login::create([
        'emp_id' => $empId,
        'emp_name' => $empname,
        'login_id' => $loginid,
        'status' => 'active',
    ]);

    if (!$emp_login) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to create session',
        ], 500);
    }

    return $response;
}

    public function gettimings() {
        $employees = Employee::all();
        $employeeCount = $employees->count();
        $employeeIds = $employees->pluck('emp_id');

        $loginRecords = Emp_login::whereIn('emp_id', $employeeIds)->get();
        $logoutRecords = Emp_logout::whereIn('login_id', $loginRecords->pluck('id'))->get();

        $attendanceData = collect();

        foreach ($loginRecords as $login) {
            $checkIn = strtotime($login->created_at);
            $checkOut = null;

            $matchingLogout = $logoutRecords->where('login_id', $login->id)->first();

            if ($matchingLogout) {
                $checkOut = strtotime($matchingLogout->created_at);
            }

            $hoursWorked = $checkOut ? round(($checkOut - $checkIn) / 3600, 2) : null;

            $attendanceData->push([
                'emp_id' => $login->emp_id,
                'check_in' => date('h:i A', $checkIn),
                'check_out' => $checkOut ? date('h:i A', $checkOut) : null,
                'hours' => $hoursWorked,
                'date' => date('d-m-Y', $checkIn),
            ]);
        }

        return [
            'attendanceData' => $attendanceData,
            'employeeCount' => $employeeCount,
            'loginRecords' => $loginRecords,
            'logoutRecords' => $logoutRecords,
            'employees' => $employees,
        ];
    }

    public function getvasoptions($id){

       $data= User::where('id',$id)->value('vas_options');
       $data1=Technicaldetails::where('id',$id)->first();
       $value=[$data,$data1
    ];
    return($value);
    }
    public function tech($id){
        $data=User::find($id);
        $vas = explode(',',$data->vas_options);
        $array=array_map('trim',$vas);
        $checkValues = [
            'Webtrack' => 'webtrack',
            'Location on Call' => 'Location on Call',
            'Ignition On' => 'Ignition On',
            'Ignition Off' => 'Ignition Off',
        ];

        foreach ($checkValues as $array => $response) {
            if (in_array($array, $array)) {
                return response()->json($response, 200);
            }
        }


        }
        public function gettime(){
            $employees = Employee::all();
            $timeDifferences = [];

            foreach ($employees as $employee) {
                $empLogins = Emp_login::where('login_id', $employee->em_loginid)
                    ->pluck('created_at', 'id')
                    ->toArray();

                if (!empty($empLogins)) {
                    $empLogouts = Emp_logout::whereIn('login_id', array_keys($empLogins))
                        ->pluck('created_at', 'login_id')
                        ->toArray();

                    foreach ($empLogins as $loginId => $loginTime) {
                        if (isset($empLogouts[$loginId])) {
                            $logoutTime = $empLogouts[$loginId];

                            $loginTime = Carbon::parse($loginTime)->setTimezone('Asia/Karachi');
                            $logoutTime = Carbon::parse($logoutTime)->setTimezone('Asia/Karachi');

                            $diffInSeconds = $logoutTime->diffInSeconds($loginTime);

                            $formattedDiff = $diffInSeconds;

                            if ($diffInSeconds >= 3600) { // If difference is in hours
                                $formattedDiff = round($diffInSeconds / 3600, 2) . ' hours';
                            } elseif ($diffInSeconds >= 60) { // If difference is in minutes
                                $formattedDiff = round($diffInSeconds / 60, 2) . ' minutes';
                            } else { // If difference is in seconds
                                $formattedDiff = $diffInSeconds . ' seconds';
                            }

                            $timeDifferences[] = [
                                'employee_id' => $employee->emp_id,
                                'emp_loginID' => $employee->em_loginid, // Include emp_name
                                'login_time' => $loginTime->toDateTimeString(),
                                'logout_time' => $logoutTime->toDateTimeString(),
                                'difference_formatted' => $formattedDiff,
                            ];
                        } else {
                            // Handle the case where there's a login but no matching logout
                            $timeDifferences[] = [
                                'employee_id' => $employee->emp_id,
                                'emp_loginID' => $employee->em_loginid, // Include emp_name
                                'login_time' => $loginTime->toDateTimeString(),
                                'logout_time' => 'Still loggedin',
                                'difference_formatted' => 'Nil',
                            ];
                        }
                    }
                }
            }
            // return ($timeDifferences);

            return view('time', compact('timeDifferences'));
        }
        public function view_renewals(Request $request)
        {
            return view('Renewals');
        }
        public function get_renewals(Request $request)
        {
            $userIds = User::pluck('id')->toArray();
            $renewals = Renewals::whereIn('client_id', $userIds)->get();
            $renewalDetails = [];

            // Fetch renewal details...
            foreach ($renewals as $renewal) {
                $renewalDetails[$renewal->client_id] = [
                    'renewal_charges' => $renewal->renewal_charges,
                    'renewal_status' => $renewal->renewal_status,
                    'renewal_date' => $renewal->renewal_date,
                    // Add other renewal details here...
                ];
            }

            // Fetching paginated user details
            $users = User::orderBy('created_at','desc')
            ->paginate(20);
            $userDetails = [];

            // Fetch user details with renewal information
            foreach ($users as $user) {
                $userDetails[] = [
                    'customer_name' => $user->customer_name,
                    'segment' => $user->segment,
                    'registeration_no' => $user->registeration_no,
                    'date_of_installation' => Carbon::parse($user->date_of_installation)->format('d-m-Y'),
                    'month' => Carbon::parse($user->date_of_installation)->format('F'),
                    'contact_no' => $user->mobileno_1,
                    'sales_person' => $user->sales_person,
                    'status' => $user->renewal_status,
                    'time' => Carbon::parse($user->created_at)->setTimezone('Asia/Karachi')->format('h:i A'),
                    'date' => Carbon::parse($user->created_at)->format('d-m-Y'),
                    // Fetch renewal details based on the client_id from the renewals table
                    'renewal_charges' => $renewalDetails[$user->id]['renewal_charges'] ?? null,
                    'renewal_status' => $renewalDetails[$user->id]['renewal_status'] ?? null,
                    // Add other renewal details here...
                ];
                $currentDate = Carbon::now()->format('Y-m-d');
                $renewalDate = isset($renewalDetails[$user->id]['renewal_date']) ? Carbon::parse($renewalDetails[$user->id]['renewal_date'])->format('Y-m-d') :null;

                if ($renewalDate !== null && $renewalDate <= $currentDate) {
                    $user->update(['renewal_status'=>'unpaid']);
                }
            }

            return view('Renewals', [
                'users' => $users,
                'renewalDetails' => $userDetails // Pass other necessary details to the view
            ]);
        }
            public function renewal_information(Request $request){
            return view ('view_renewal_information');
        }

        public function get_renewals_info($regNo){
            $user = User::where('registeration_no', $regNo)->first();
            $renewalDetails = [];

            if ($user) {
                $renewal = Renewals::where('reg_no', $regNo)->first();
                $client_id=Technicaldetails::where('client_code',$user->id)->first();
                if($client_id){
         $date_of_installation=$client_id->created_at;
         $doi=$date_of_installation->format('d-m-Y');
                }
                $created_at=$user->created_at;
                $datetime= new DateTime($created_at);
                $month=$datetime->format('F');
                if ($renewal) {
                    $renewalDetails = [
                        'renewal_id'=>$renewal->id,
                        'renewal_charges' => $renewal->renewal_charges,
                        'renewal_status' => $renewal->renewal_status,
                        'renewal_date' => isset($renewal->renewal_date) ? date('d-m-Y', strtotime($renewal->renewal_date)) : null,
                        'recieved_renewal'=>$renewal->recieved_renewal,
                        // Add other renewal details here...
                    ];
                }

                $responseData = [
                    'success' => true,
                    'segment' => $user->segment,
                    'customer_name' => $user->customer_name,
                    'contact' => $user->mobileno_1,
                    'tracker-charges' => $user->tracker_charges,
                    'Reg-no' => $user->registeration_no,
                    'sales-person' => $user->sales_person,
                    'month' => $month,
                    'city' => $user->installation_loc ??'-',
                    'date-of-installation' => $doi??'-',
                    'engine' => $user->engine_no,
                    'model' => $user->model,
                    'reference' => $renewalDetails['id']?? '-',
                    'chasis-no' => $user->chasis_no,
                    'renewal_charges' => $renewalDetails['renewal_charges'] ?? '-',
                    'renewal_status' => $renewalDetails['renewal_status'] ?? '-',
                    'renewal-date' => $renewalDetails['renewal_date'] ?? '-',
                    'renewal_id'=>$renewalDetails['renewal_id']??'-',
                    'amount'=>$renewalDetails['recieved_renewal']??'-',

                    // Other fields...
                ];

               return view('view_renewal_information')->with('data',$responseData);
            } else {
                // If user not found
                return response()->json(['message' => 'data not found'], 404, [], JSON_PRETTY_PRINT);
            }
        }
        public function create_renewal_remarks(Request $request){
            try {
                $sessionToken = $request->session()->get('session_token');
                $empId = $request->session()->get('em_loginid_' . $sessionToken);
                $emp_name = null; // Initialize $emp_name to handle cases where $empId is not found
                if ($empId) {
                    $emp_name = Employee::where('em_loginid', $empId)->value('emp_name');
                }

                $validatedData = $request->validate([
                    'remarks' => 'required',
                    'renewal_id' => 'required'
                ]);

                if ($validatedData) {
                    $value = [
                        'renewal_id' => $request->renewal_id,
                        'remarks' => $request->remarks,
                        'representative' => $emp_name,
                    ];
                    $remarks = Renewals_remarks::create($value);
                    if ($remarks) {
                        return response()->json(['message' => 'Remarks submitted successfully'], 200);
                    }
                }
            } catch (\Exception $e) {
                // Log the error to identify the issue
                \Log::error('Error submitting remarks: ' . $e->getMessage());
                return response()->json(['message' => 'Error submitting remarks. Please try again.'], 500);
            }
        }


        public function get_renewal_remarks( Request $request){
            $data = Renewals_remarks::where('renewal_id', $request->renewal_id)
            ->orderBy('created_at','desc')
            ->get();
            $details = [];

            if ($data) {
                foreach ($data as $info) {
                    $time=Carbon::parse($info->created_at)->timezone('Asia/Karachi')->format('h:i A');
                    $details[] = [
                        'Renewal-id' => $info->renewal_id,
                        'Remarks' => $info->remarks,
                        'Representative' => $info->representative,
                        'Date'=>Carbon::parse($info->created_at)->format('d-m-Y'),
                        'Time'=>$time,
                        'amount'=>$info->recieved_renewal,
                    ];
                }
                return response()->json($details, 200);
            } else {
                return response()->json(['message' => 'No data found'], 404);
            }
        }

public function add_renewal_payement($regNo){
    $user = User::where('registeration_no', $regNo)->first();
            $renewalDetails = [];

            if ($user) {
                $renewal = Renewals::where('reg_no', $regNo)->first();
                $client_id=Technicaldetails::where('client_code',$user->id)->first();
                if($client_id){
         $date_of_installation=$client_id->created_at;
         $doi=$date_of_installation->format('d-m-Y');
                }
                $created_at=$user->created_at;
                $datetime= new DateTime($created_at);
                $month=$datetime->format('F');
                if ($renewal) {
                    $renewalDetails = [
                        'renewal_id'=>$renewal->id,
                        'renewal_charges' => $renewal->renewal_charges,
                        'renewal_status' => $renewal->renewal_status,
                        'renewal_date' => date('d-m-Y',strtotime($renewal->renewal_date)),
                        // Add other renewal details here...
                    ];
                }

                $responseData = [
                    'success' => true,
                    'segment' => $user->segment,
                    'customer_name' => $user->customer_name,
                    'contact' => $user->mobileno_1,
                    'tracker-charges' => $user->tracker_charges,
                    'Reg-no' => $user->registeration_no,
                    'sales-person' => $user->sales_person,
                    'month' => $month,
                    'city' => $user->installation_loc ??'-',
                    'date-of-installation' => $doi ??'-',
                    'engine' => $user->engine_no,
                    'model' => $user->model,
                    'reference' => $renewalDetails['id']?? '-',
                    'chasis-no' => $user->chasis_no,
                    'renewal_charges' => $renewalDetails['renewal_charges'] ?? '-',
                    'renewal_status' => $renewalDetails['renewal_status'] ?? '-',
                    'renewal-date' => $renewalDetails['renewal_date'] ?? '-',
                    'renewal_id'=>$renewalDetails['renewal_id']??'-',
                    // Other fields...
                ];

               return view('Addpayment_renewal')->with('data',$responseData);
            } else {
                // If user not found
                return response()->json(['message' => 'data not found'], 404, [], JSON_PRETTY_PRINT);
            }

}
public function store_add_renewalpayment(Request $request){
    $sessionToken = $request->session()->get('session_token');
    $empId = $request->session()->get('em_loginid_' . $sessionToken);
    if($empId){
        $emp_name=Employee::where('em_loginid',$empId)->value('emp_name');

    }
$validatedData = $request->validate([
    'status' => 'required',
    'renewal_id'=>'required',
    // 'remarks'=>'required',
    'recieved_renewal'=>'required',
    // 'representative'=>'required',
]);
if($validatedData){
$value=[
    'renewal_id'=>$request->renewal_id,
    'remarks'=>$request->remarks??'Payment has been transacted',
    'recieved_renewal'=>$request->recieved_renewal,
    'representative'=>$emp_name,

];
$data=Renewals_remarks::create($value);
$renewal=Renewals::where('id',$request->renewal_id)->first();

if ($request->status == 'removed') {
    // Update renewal status to 'pending' when status is 'removed'
    $renewal->update(['renewal_status' => 'pending']);

}
else if ($request->status == 'paid') {
    // Update renewal date and status for 'paid' status
    $renewalDate = Carbon::parse($renewal->renewal_date);
    $renewal->renewal_date = $renewalDate->addYear();
    $renewal->update(['renewal_status' => 'paid']);
}
 else  {
    // Update renewal status for any other status
    $renewal->update(['renewal_status' => $request->status]);
}


if($data && $renewal ){


    return response()->json(['message' => 'Remarks submitted successfully'], 200);
}
else{
    return response()->json(['message' => 'Internal server error'], 200);

}
}
}






public function update_renewal($regNo){
    $user = User::where('registeration_no', $regNo)->first();
    $renewalDetails = [];

    if ($user) {
        $renewal = Renewals::where('reg_no', $regNo)->first();

        if ($renewal) {
            $renewalDetails = [
                'renewal_id'=>$renewal->id,
                'renewal_charges' => $renewal->renewal_charges,
                'renewal_status' => $renewal->renewal_status,
                'renewal_date' => $renewal->renewal_date,
                // Add other renewal details here...
            ];
        }
       $client_id=Technicaldetails::where('client_code',$user->id)->first();
       if($client_id){
$date_of_installation=$client_id->created_at;
$doi=$date_of_installation->format('d-m-Y');
       }

        $created_at=$user->created_at;
        $datetime= new DateTime($created_at);
        $month=$datetime->format('F');
        $responseData = [
            'success' => true,
            'segment' => $user->segment,
            'customer_name' => $user->customer_name,
            'contact' => $user->mobileno_1,
            'tracker-charges' => $user->tracker_charges,
            'Reg-no' => $user->registeration_no,
            'sales-person' => $user->sales_person,
            'month' => $month,
            'city' => $user->installation_loc ??'-',
            'date-of-installation' => $doi ??'-',
            'date-of-registeration'=>$created_at->format('d-m-Y'),
            'engine' => $user->engine_no,
            'model' => $user->model,
            'reference' => $renewalDetails['id']?? '-',
            'chasis-no' => $user->chasis_no,
            'renewal_charges' => $renewalDetails['renewal_charges'] ?? '-',
            'renewal_status' => $renewalDetails['renewal_status'] ?? '-',
            'renewal-date' => $renewalDetails['renewal_date'] ?? '-',
            'renewal_id'=>$renewalDetails['renewal_id']??'-',
            'client_id'=>$user->id,
            // Other fields...
        ];

        return view('update_renewal')->with('data',$responseData);
    } else {
        // If user not found
        return response()->json(['message' => 'data not found'], 404, [], JSON_PRETTY_PRINT);
    }

}
public function update_status_renewal(Request $request){
    {

$validatedData = $request->validate([
    'status' => 'required',
    'renewal_id'=>'required',
]);
if($validatedData){
  $status=  Renewals::where('id',$request->renewal_id)
    ->update(['renewal_status'=>$request->status]);
    if($status ){
        return response()->json(['message' => 'status updated successfully'], 200);
    }
    else{
        return response()->json(['message' => 'Internal server error'], 200);

    }
}
}

}
public function testapi(Request $rqeuest){
    $lastComplaint = User::latest()->first();
    $lastComplaintId = $lastComplaint ? $lastComplaint->id + 1 : 1;
    return view('testajax', compact('lastComplaintId'));}
public function storeajax(Request $request){
    $validatedData = $request->validate([
        'id' => 'required',
        'customer_name' => 'required',
        'father_name' => 'required',
        'address' => 'required',
        'telephone_residence' => 'required', // Example: Must be a number
        'mobileno_1' => 'required',
        'mobileno_2' => 'nullable', // Example: Nullable if not always required
        'mobileno_3' => 'nullable',
        'mobileno_4' => 'nullable',
        'ntn' => 'required|unique:users,ntn', // Example: Unique in the 'users' table's 'ntn' column
        'cnic' => 'required',
        'primaryuser_name' => 'required',
        'primaryuser_con1' => 'required',
        'primaryuser_con2' => 'nullable',
        'primaryuser_cnic' => 'required',
        'seconadryuser_name' => 'required',
        'secondaryuser_con1' => 'required',
        'secondaryuser_con2' => 'nullable',
        'relationship' => 'required',
        'registeration_no' => 'required|unique:users,registeration_no',
        'chasis_no' => 'required',
        'engine_no' => 'required',
        'engine_type' => 'required',
        'CC' => 'required',
        'make' => 'required',
        'model' => 'required',
        'year' => 'required|numeric',
        'transmission' =>'required',
        'color' => 'required',
        'insurance_partner' => 'required',
        'vas' => 'required',
        'vas_options' => 'required|array',
        'segment' => 'required',
        'demo_duration' => 'required',
        'tracker_charges' => 'required',
        'date_of_installation' => 'required',
        'int_comission' => 'required',
        'ext_comission' => 'required',
        'discount' => 'required',
        'campaign_point' => 'required',
        'dealership' => 'required',
        'dealer_name' => 'required',
        'sales_person' => 'required',
        'installation_loc' => 'required',
        'conatct_person' => 'required',
        'remarks' => 'required',

    ]);
    if(!$validatedData){
        return redirect()->back()->withErrors($validatedData)->withInput();

    }

    $data= new User();

        $data->id=$request->input('id');
        $data->customer_name=$request->input('customer_name');
        $data->father_name=$request->input('father_name');
        $data->address=$request->input('address');
        $data->telephone_residence=$request->input('telephone_residence');
        $data->mobileno_1=$request->input('mobileno_1');
        $data->mobileno_2=$request->input('mobileno_2');
        $data->mobileno_3=$request->input('mobileno_3');
        $data->mobileno_4=$request->input('mobileno_4');
        $data->ntn=$request->input('ntn');
        $data->cnic=$request->input('cnic');
        $data->primaryuser_name=$request->input('primaryuser_name');
        $data->primaryuser_con1=$request->input('primaryuser_con1');
        $data->primaryuser_con2=$request->input('primaryuser_con2');
        $data->primaryuser_cnic=$request->input('primaryuser_cnic');
        $data->seconadryuser_name=$request->input('seconadryuser_name');
        $data->secondaryuser_con1=$request->input('secondaryuser_con1');
        $data->secondaryuser_con2=$request->input('secondaryuser_con2');
        $data->relationship=$request->input('relationship');
        $data->registeration_no=$request->input('registeration_no');
        $data->chasis_no=$request->input('chasis_no');
        $data->engine_no=$request->input('engine_no');
        $data->engine_type=$request->input('engine_type');
        $data->CC=$request->input('CC');
        $data->make=$request->input('make');
        $data->model=$request->input('model');
        $data->year=$request->input('year');
        $data->transmission=$request->input('transmission');
        $data->color=$request->input('color');
        $data->insurance_partner=$request->input('insurance_partner');

        $data->vas=$request->input('vas');
        $vasOptions = $request->input('vas_options', []);
        $data->vas_options = implode(',', $vasOptions);

        $data->segment=$request->input('segment');
        $data->demo_duration=$request->input('demo_duration');
        $data->tracker_charges=$request->input('tracker_charges');
        $data->date_of_installation=$request->input('date_of_installation');
        $data->int_comission=$request->input('int_comission');
        $data->ext_comission=$request->input('ext_comission');
        $data->discount=$request->input('discount');
        $data->campaign_point=$request->input('campaign_point');
        $data->dealership=$request->input('dealership');
        $data->dealer_name=$request->input('dealer_name');
        $data->sales_person=$request->input('sales_person');
        $data->installation_loc=$request->input('installation_loc');
        $data->conatct_person=$request->input('conatct_person');
        $data->remarks=$request->input('remarks');
        $data->form_status='declined';

        $data->save();
        DB::commit();

        if($data ){
            return response()->json(['message' => 'data submitted successfully'], 200);
        }
        else{
            return response()->json(['message' => 'Internal server error'], 200);
        }

}
public function check_in(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'emp_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
                'data' => null
            ], 400);
        }

        $currenttime = Carbon::now('Asia/Karachi');
        $time=$currenttime->format('h:i A');

        // You might need to fetch the employee by the card scanner information here
        // For this example, assuming 'emp_id' is the unique identifier in the request
        $employee = Employee::where('emp_id', $request->emp_id)->first();

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee not found',
            ], 404);
        }

       $value=[
        'emp_id'=>$request->emp_id,
        'checkin_time'=>$time,
        'status'=>'active',
       ];
       $data=Attendance::create($value);
       if($data){

        return response()->json([
            'success' => true,
            'message' => 'Attendance created successfully',
        ], 200);
    }
    else{
        return response()->json([
            'success' => false,
            'message' => 'Internal server error',
        ], 200);
    }
}
public function checkInOut(Request $request)
{
    $validator = Validator::make($request->all(), [
        'emp_id' => 'required|exists:workers,emp_id',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => $validator->errors(),
            'data' => null
        ], 400);
    }

    $karachiTimezone = 'Asia/Karachi';
    $currentTime = Carbon::now($karachiTimezone);

    // Find the latest entry for the employee within the last 24 hours
    $lastEntry = Attendance::where('emp_id', $request->emp_id)
        ->where('created_at', '>=', $currentTime->copy()->subHours(24)->format('Y-m-d H:i:s'))
        ->first();

    if ($lastEntry) {
        $checkInTime = Carbon::parse($lastEntry->checkin_time);
        $allowedCheckOutTime = $checkInTime->copy()->addHours(5)->format('Y-m-d H:i:s');

        if ($lastEntry->status === 'active' && $currentTime->lessThan($allowedCheckOutTime)) {
            // Cannot check out before 5 hours of check-in
            return response()->json([
                'success' => false,
                'message' => 'Cannot check out before 5 hours of check-in.',
            ], 403);
        }

        if ($lastEntry->status === 'active') {
            // Perform the check-out process
            $lastEntry->checkout_time = $currentTime->setTimezone($karachiTimezone)->format('h:i A');
            $lastEntry->status = 'inactive';
            $lastEntry->save();

            return response()->json([
                'success' => true,
                'message' => 'Checked out successfully.',
            ], 200);
        } else {
            // Employee has already checked out after the last check-in
            return response()->json([
                'success' => false,
                'message' => 'Employee has already checked out after the last check-in.',
            ], 403);
        }
    } else {
        // Perform the check-in process
        $checkInValue = [
            'emp_id' => $request->emp_id,
            'checkin_time' => $currentTime->setTimezone($karachiTimezone)->format('h:i A'),
            'status' => 'active',
        ];

        $data = Attendance::create($checkInValue);

        if ($data) {
            return response()->json([
                'success' => true,
                'message' => 'Checked in successfully.',
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Internal server error.',
            ], 500);
        }
    }
}
public function manageAttendance(Request $request)
{
    // $employeeId = $request->input('emp_id');
    $validator = Validator::make($request->all(), [
        'emp_id' => 'required|exists:workers,emp_id',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => $validator->errors(),
            'data' => null
        ], 400);
    }

    // Set the timezone to Karachi
    date_default_timezone_set('Asia/Karachi');

    // Get the current time in Karachi timezone
    $karachi_time = Carbon::now();
    $date = $karachi_time->toDateString();

    // Check if the employee has already checked in today
    $existingRecord = Attendance::where('emp_id', $request->emp_id)
        ->whereDate('date', $date)
        ->first();

    if (!$existingRecord) {
        // Record check-in time
        Attendance::create([
            'emp_id' => $request->emp_id,
            'checkin_time' => $karachi_time->format('H:i:s'),
            'date' => $date,
            'status' => 'active'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Check-in successful',
            'data' => $date,
            'Time' => $karachi_time->format('H:i:s'),
        ]);
    }
    else {
        if ($existingRecord->checkout_time) {
            return response()->json([
                'success' => false,
                'message' => 'You have already checked out today',
            ]);
        }

        // Check-out logic
        if ($existingRecord->checkout_time==null && $existingRecord->checkin_time==true) {
        $checkInTime = Carbon::parse($existingRecord->checkin_time);
        $currentTime = $karachi_time;

        // Calculate the duration between check-in time and current time
        $duration = $checkInTime->diff($currentTime);
        $totalHours = $duration->h + ($duration->days * 24);

        if ($totalHours >= 5) {
            $existingRecord->update([
                'checkout_time' => $karachi_time->format('H:i:s'),
                'status' => 'inactive'
            ]);
              return response()->json([
                'success' => true,
                'message' => 'Checkedout successfully!',
                'duration'=>$totalHours
            ]);

        }
        else{
            return response()->json([
                'success' => false,
                'message' => 'Not accessable until 5 hours has been completed!',

            ]);
        }
}
}

return response()->json([
    'success' => false,
    'message' => 'Internal server error',


],500);


}
public function get_all_record(Request $request)
{
    // Fetch all attendance records
    $attendances = Attendance::all();

    // Format check-in and check-out times to AM/PM format
    $formattedAttendances = $attendances->map(function ($attendance) {
        return [
            'emp_id' => $attendance->emp_id,
            'checkin_time' => $attendance->checkin_time ? Carbon::parse($attendance->checkin_time)->format('h:i:s A') : null,
            'checkout_time' => $attendance->checkout_time ? Carbon::parse($attendance->checkout_time)->format('h:i:s A') : null,
            'date'=>$attendance->date,
        ];
    });

    // Ensure the response is properly formatted as JSON
    return response()->json($formattedAttendances->values()->all(), 200);

}
public function view_profle(Request $request){
    $validator = Validator::make($request->all(),[
        'emp_id' => 'required|string'
    ]);
    
 if ($validator->fails()) {
    return response()->json($validator->messages(), 400);
}
    // $sessionToken = $request->session()->get('session_token');
    // $empId = $request->session()->get('emp_id_' . $sessionToken);
   
$emp=Employee::where('emp_id',$request->emp_id)
->first();
if($emp){

// return view('editprofile')->with(['data'=>$emp]);
return response()->json([
    'success'=>true,
    'message'=>'Data found successfully',
    'data'=>$emp
], 200, );
}
else{
    return response()->json([
        'success'=>false,
        'message'=>'Data not found successfully',
        'data'=>null
    ], 200, );
}
    }

public function editprofile(request $request)
{
    $validator = Validator::make($request->all(), [
        'emp_name'=>'required',
        'old_login_id' => 'required|exists:workers,em_loginid',
        // 'new_login_id'=>'required',
        'old_password' => 'required',
        'new_password' => 'required|confirmed',
        'new_password_confirmation' => 'required_with:new_password|same:new_password',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => $validator->errors(),
            'data' => null
        ], 400);
    }
   $emp= Employee::where('em_loginid',$request->old_login_id)
    ->where('password',$request->old_password)
    ->first();

if($emp){
$emp->update([
'emp_name'=>$request->emp_name,
'password'=>$request->new_password]);

return response()->json([
    'success'=>true,
    'message'=>'Credentials updated successfully',
    'data'=>$emp

], 200, );
}
if(!$emp){
    return response()->json([
        'success' => false,
        'message' => 'Invalid old password or login ID',
        'data' => null
    ], 400);

}
else{
    return response()->json('Internal serve error', 400,);
}
}
public function formualte( $regNo)
{
    // $regNo = $request->input('reg_no');

     $user = User::where('registeration_no', $regNo)->first();
     if (!$user) {
        return response()->json('This car has been removed', 400,);
    }


     $device=Removal::where('client_id',$user->id)
     ->select('device')->first();
     $technical =Technicaldetails::where('client_code',$user->id)->select('mobilizer')->first();

     if ($device && $technical) {
         // If a user is found, you can pass the user data to the view
        //  return view('removaltransfer', compact('user','device','technical'));
        return response()->json([
            'success'=>true,
            'user'=>$user,
            'device'=>$device,
            'technical'=>$technical

        ],JSON_PRETTY_PRINT);

     }
     else{
        return response()->json('This car is not removed till now', 400,);
     }

}

public function last_30_days_datalogs(Request $request)
{
    // Get the date 30 days ago from today
    $dateThirtyDaysAgo = Carbon::now()->subDays(30);

    $data = Datalogs::whereDate('created_at', '>=', $dateThirtyDaysAgo)->get();
    $customer_names = [];

    foreach ($data as $cs_name) {
        $customer_names[] = [
            'customer_name' => $cs_name->customer_name,
            'reg-no' => $cs_name->reg_no,
            'contact-mo' => $cs_name->contact_no,
            'Contact-person' => $cs_name->contact_person,
            'Remarks' => $cs_name->remarks,
            'Representative' => $cs_name->representative,
            'Nature-of-alert' => $cs_name->nature,
            'date' => $cs_name->created_at->format('d-m-Y'),
            'time' => $cs_name->created_at->setTimezone('Asia/karachi')->format('h:i A'),
        ];
    }



    return response()->json([
        'success' => true,
        'data' => $customer_names,
    ], 200, []);
}
public function view_data_logs(Request $request){
    return view('view_all_datalogs');
}
public function view30days(Request $request){
    return view('last_30day_datalogs');
}
public function view_all_data_logs(Request $request)
{




    $data = Datalogs::orderBy('created_at', 'desc')
        ->paginate(20);

    return view('view_all_datalogs')->with('data',$data);
}
public function view_all_complain_logs(Request $request){
    return view('all_complain_logs');
}

public function all_complain_logs(Request $request)
{
    $complain = Complain::join('users', 'complain.client_id', '=', 'users.id')
    ->orderBy('created_at','desc')
        ->select(
            'complain.customer_name',
            'complain.reg_no',
            'complain.nature_of_complain',
            'complain.remarks',
            'complain.Status',
            'complain.created_at',
            'complain.emp_name',
        )
        ->addSelect('users.mobileno_1')
        ->paginate(20); // Paginate the results

    // If you need to modify data, you can use a map function


    return view('all_complain_logs')->with('data', $complain);
}

public function search_by_customer_name( $customer){

$search_term=$customer;
$data=User::where('customer_name','LIKE',"%$search_term%")
->orderBy('created_at', 'desc')
->first();
return response()->json([
    'success'=>true,
    'data'=>$data,
], 200, );

if(!$data){

    return response()->json([
        'success'=>false,
        'data'=>null,
    ], 200, );



}

}
public function get_Reg_No($customer_name){
    $data = User::where('customer_name', $customer_name)
    ->pluck('registeration_no')
    ->unique()
    ->values();

    return response()->json([
        'success' => true,
        'Reg-NO' => $data,
    ], 200);
}

public function logs(Request $request){
    $days = $request->query('days', 10); // Default to 10 days if no value is provided
    $startDate = now()->subDays($days)->startOfDay();
    $endDate = now()->endOfDay();

    $data = Datalogs::whereBetween('created_at', [$startDate, $endDate])
        ->orderBy('created_at', 'desc')
        ->paginate(20);

    return view('view_all_datalogs',compact('data'));
}

public function get_renewal_removals(Request $request)
{
    $data = Renewals::where('renewal_status', 'pending')
    ->orderBy('created_at', 'desc')
    ->get();



return view('renewals_removals',['data'=> $data]);
}
public function action_removal_renewals(Request $request){
    $id = $request->input('id');
    if($id){
        $removal = Renewals::where('id', $id)
            ->update(['renewal_status' => 'removed']);

        if($removal !== false){
            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully'
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Internal server error'
            ], 500);
        }
    } else {
        return response()->json([
            'success' => false,
            'message' => 'Invalid ID provided'
        ], 400);
    }
}


public function getcurrenttime(Request $request){
$date=carbon::now();
$current_date=$date->format('d-m-Y');
$addyear = $date->copy()->addYear();
$addyears_formatted=$addyear->format('d-m-Y');
if ($date->lessThanOrEqualTo($addyear)) {
    $diffInHours = $date->diffInHours($addyear);

return response()->json([
    'success'=>true,
     'message'=>' greater',
     'data'=>$diffInHours

], 200, );
}
else{
    return response()->json([
        'success'=>false,
         'message'=>'not greater'

    ], 200, );
}
}
public function view_dashboard(Request $request){
    return view('dashboard');

}
public function all_inventory(Request $request){
$all_devices=Deviceinventory::orderBy('created_at','desc')
->paginate(20);

return view('All_device_inventory')->with('data',$all_devices);
}
public function view_update_inventory(Request $request){
return view('Update_device_info');

}
public function update_device_inventory(Request $request){
    $regNo = $request->input('device_serial');
    $device = Deviceinventory::where('device_serialno', $regNo)
    ->orWhere('imei_no','LIKE',"%$regNo%")
    ->orWhere('devciesim_no','LIKE',"%$regNo%")
    ->orderBy('created_at','desc')
    ->first();
    if (!$device) {
       return redirect()->back()->with('error', 'Data not found.');
   }
//    $technical =Technicaldetails::where('client_code',$user->id)->select('mobilizer','device_id')->first();

   if ($device) {
       return view('Update_device_info', compact('device'));
   }
}
public function update_device(Request $request){
    $validator = Validator::make($request->all(), [
        'device_serialno'=>'required',


    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => $validator->errors(),
            'data' => null
        ], 400);
    }
    $device=Deviceinventory::where('device_serialno',$request->device_serialno)->first();
if($device){
DB::beginTransaction();
    $device->imei_no=$request->imei_no;
    $device->vendor=$request->vendor;
    $device->devciesim_no=$request->devciesim_no;
    $device->save();
DB::commit();
    return response()->json([
        'success'=>true,
      'message'=>'submitted successfully'
    ], 200, );
}
else{
    return response()->json([
        'success'=>false,
        'message'=>'error in submission'
    ], 200, );
}

}
public function active_devices(Request $request){
    $activeDevices =Deviceinventory::where('status','active')
    ->orderBy('created_at','desc')
    ->paginate(6);
    return view('active_devices',['activeDevices'=>$activeDevices]);


    }
    public function inactive_devices(Request $request){
        $In_active =Deviceinventory::where('status','inactive')
        ->orderBy('created_at','desc')
        ->paginate(6);
        return view('inactive_devices',['inactiveDevices'=>$In_active]);



    }
    public function sms_box(Request $request){
        $search = $request->input('serch_term');

        $user = User::where('registeration_no', $search)
        ->orWhere('customer_name','LIKE',"%$search%")
        ->orderBy('created_at','desc')
        ->first();
        $sms_type=DB::table('sms_type')->pluck('sms_type');
        if ($user) {
            // If a user is found, you can pass the user data to the view
            return view('sms box', compact('user','sms_type'))->with('success', 'Data found.');
        } else {
            return redirect()->back()->with('error', 'Data not found.')->withInput();
        }
    }
    public function create_sms_service(Request $request){

        $sessionToken = $request->session()->get('session_token');
        $empId = $request->session()->get('emp_id_' . $sessionToken);
        $empName=Employee::where('emp_id',$empId)
        ->value('emp_name');
        $validator = Validator::make($request->all(), [
            'client_id'=>'required',
            'customer_name'=>'required',
            'reg_no' => 'required',
            'contact' => 'required',
            'sms_type' => 'required',
            'message' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
                'data' => null
            ], 400);
        }
        $value=[
            'client_id'=>$request->client_id,
            'customer_name'=>$request->customer_name,
            'reg_no'=>$request->reg_no,
            'contact'=>$request->contact,
            'sms_type'=>$request->sms_type,
            'message'=>$request->message,
            'representative'=>$empName,
        ];
       $data=SMS::create($value);
       if($data){
        return response()->json([
            'success'=>true,
            'message'=>'sms created successfully'
        ], 200,);
       }
       if(!$data){
        return response()->json([
            'success'=>false,
            'message'=>'error!'
        ], 200,);

       }

    }
    public function chunk(Request $request){
        $chunkSize = $request->input('chunk_size', 100); // Default chunk size is 100, but you can change it as needed
        $data = [];

        User::chunk($chunkSize, function ($users) use (&$data) {
            foreach ($users as $user) {
                // Process each user if needed
                $data[] = $user; // Assuming you want to collect users' data
            }
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ], 200);
    }
public function sms(Request $request){
$data=DB::table('sms_type')->pluck('sms_type');
return response()->json($data, 200);
}
public function export_to_pdf(Request $request){
    $devices = Deviceinventory::orderBy('created_at','desc')
    ->get();

    // Create an HTML table to structure the data

    // Generate PDF with the structured HTML
    $pdf = PDF::loadView('pdf.inventory',compact('devices'))->setPaper('a4','landscape');
    return $pdf->stream();
}
public function download_pdf(Request $request){
    $devices = Deviceinventory::orderBy('created_at','desc')->get();

    // Create an HTML table to structure the data

    // Generate PDF with the structured HTML
    $pdf = PDF::loadView('pdf.inventory', compact('devices'))->setPaper('a4','landscape');

    // Change return method to download()
    return $pdf->download('inventory.pdf');
}

public function download_renewals(Request $request) {
    // Fetch all user IDs
    $userIds = User::pluck('id')->toArray();

    // Fetch renewals for the retrieved user IDs
    $renewals = Renewals::whereIn('client_id', $userIds)->get();
    $renewalDetails = [];

    // Fetch renewal details...
    foreach ($renewals as $renewal) {
        $renewalDetails[$renewal->client_id] = [
            'renewal_charges' => $renewal->renewal_charges,
            'renewal_status' => $renewal->renewal_status,
            'renewal_date' => $renewal->renewal_date,
            // Add other renewal details here...
        ];
    }

    // Fetch all users with their details
    $users = User::orderBy('created_at', 'desc')->get();
    $userDetails = [];

    // Fetch user details with renewal information
    foreach ($users as $user) {
        // Skip users who don't have renewal details
        if (isset($renewalDetails[$user->id])) {
            $userDetails[] = [
                'customer_name' => $user->customer_name,
                'segment' => $user->segment,
                'reg_no'=>$user->registeration_no,
                'contact_no'=>$user->mobileno_1,
                'date' => Carbon::parse($user->created_at)->format('d-m-Y'),
                'renewal_date'=>$renewalDetails[$user->id]['renewal_date'] ?? null,
                'renewal_charges' => $renewalDetails[$user->id]['renewal_charges'] ?? null,
                'renewal_status' => $renewalDetails[$user->id]['renewal_status'] ?? null,
                // ... (other renewal details)
            ];
        }
    }

    $pdf = PDF::loadView('pdf.renewals', compact('userDetails'))->setPaper('a4', 'landscape');
    return $pdf->stream();
}
public function forgetpassword(Request $request){
    return view('forgetPassword');
}
public function create_forgetpass(Request $request){
    $validator = Validator::make($request->all(), [
        'login_id'=>'required',



    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Validation error',
            'errors' => $validator->errors(),
        ], 422);
    }
   $emp= Employee::where('em_loginid',$request->login_id)->first();
   if($emp){
   if($emp->forget_password_status=='accepted'||$emp->forget_password_status==null){
    $emp->forget_password_status = 'pending';
    $emp->save();
    // $emp->update(['forget_password_status'=>'pending']);
    return response()->json([
        'success'=>true,
        'response'=>'your request has been forwarded',

    ], 200, );
   }

   if($emp->forget_password_status=='pending'){
    return response()->json([
        'success'=>true,
        'response'=>'your request has been forwarded to admin please wait',
    ], 200, );
   }
   }
   else{
    return response()->json([
        'success'=>false,
        'response'=>'Please enter correct login name/id'
    ], 200, );

   }
}
public function get_forgetpassword(Request $request){
    $employees=Employee::where('forget_password_status','pending')
->orderBy('created_at','desc')
->get();
return view('forget_password_request',compact('employees'));
}
public function action_forget_password(Request $request){
    $validator = Validator::make($request->all(), [
        'emp_id'=>'required',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Validation error',
            'errors' => $validator->errors(),
        ], 422);
    }
    $emp=Employee::where('emp_id',$request->emp_id)->first();
    if($emp){
        $emp->forget_password_status='accepted';
        $emp->save();
        return response()->json([
            'success' => true, // or false in case of failure
            'response' => 'Success message or error message',
        ], 200, );
    }
}
public function employees_record(Request $request)
{
   $record= Attendance::get();
   $data=[];
   foreach($record as $value){
    $data[]=[
        'Emp-id'=>$value->emp_id,
      'checkintime'=> $value->checkin_time,
      'checkouttime'=> $value->checkout_time,
      'Date'=>$value->date,
      'Status'=>$value->status,

    ];

   }
  return view('Attendance Record',['data'=>$data]);
}
public function joins(Request $request){
$emp= Employee::join('_employee_attendance','workers.emp_id','=','_employee_attendance.emp_id')
->orderBy('workers.created_at','desc')
->select(
    'workers.emp_id',
    'workers.em_loginid',
    'workers.password',
    'workers.created_at as emp_created_at',

    '_employee_attendance.checkin_time'

)


->get();
foreach($emp as $record){
    $record->checkin_time=date('h:i A',strtotime($record->checkin_time));
    $record->emp_created_at=date('d-m-Y',strtotime($record->emp_created_at));

}


 return response()->json([
    'success'=>true,
    'data'=>$emp
 ], 200, );
}
public function dollar_rate(Request $request){
    $apiKey = 'YOUR_OPEN_EXCHANGE_RATES_API_KEY';
        $baseCurrency = 'USD';
        $targetCurrency = 'PKR';

        $client = new Client();
        $response = $client->request(
            'GET',
            "https://open.er-api.com/v6/latest/{$baseCurrency}?apikey={$apiKey}"
        );

        if ($response) {
            $data = json_decode($response->getBody(), true);

            $exchangeRate = $data['rates'][$targetCurrency];

            // return response()->json([
            //     'success'=>true,
            //     'Dollar into PKR today' => $exchangeRate]);
return view('dollar_rate',compact('exchangeRate'));

        }

        return response()->json([
            'success'=>false,
            'error' => 'Failed to fetch exchange rate'], 500);

}
public function all_vehicle_details(Request $request) {
    $data = User::OrderBy('created_at','desc')
    ->get();
    $count = $data->count();

    $status = [];
    foreach ($data as $user) {
        $userStatus = $user->client_code()->select('operational_status', 'tracker_status')->first();
        if ($userStatus) {
            $status[] = $userStatus;
        }
    }

    // return view('completeDetails', compact('data', 'count', 'status'));
    return response()->json([
        'success'=>true,
        'mes'=>'all records found',
        'data'=>$data,
        'status'=>$status,
        'count'=>$count


    ], 200, );
}
public function get_roles(Request $request){
    $roles=DB::table('roles')
    ->pluck('roles');
    if($roles){
    return response()->json([
        'success'=>true,
        'message'=>'roles fetched successfully',
        'data'=>$roles,
    ], 200, );
}
else{
    return response()->json([
        'success'=>false,
        'message'=>'roles fetched unsuccessfully',
        'data'=>null
    ], 200, );
}

}
public static function test_api(Request $request){
    $validator=Validator::make($request->all(),[
        'reg_no'=>'required',
        'eng_no'=>'required',
        'chasis_no'=>'required'
    ]);
    if($validator->fails()){
        return response()->json([
            'success'=>false,
            'message'=>$validator->errors(),
        ], 200, );
    }
        $data=[
            'reg_no'=>$request->reg_no,
        'eng_no'=>$request->reg_no,
        'chasis_no'=>$request->reg_no,
        ];
       $test= DB::table('test')->insert($data);
if($test){
    return response()->json([
        'success'=>true,
        'message'=>'data submitted successfully',
        'data'=>$data
    ], 200, );
}
else{
  
        return response()->json([
            'suucess'=>false,
            'message'=>'internal server error',
            'data'=>null
        ], 200, );

}
       }
       public function service_order_form(Request $request){
        $validator=Validator::make($request->all(),[
            'search_term'=>'required'
        ]);
        if($validator->fails()){
            return response()->json([
                'success'=>false,
                'message'=>$validator->errors()
            ], 200, );
        }
        $input=$request->search_term;
        $user=User::where('registeration_no',$input)
        ->orWhere('customer_name','LIKE','%'.$input.'%')
        ->orWhere('engine_no',$input)
        ->orWhere('chasis_no',$input)
        ->orderBy('created_at','desc')
        ->first();
       
       if($user){
        return response()->json([
            'success'=>true,
            'message'=>'Details fetched successfully',
            'data'=>$user,
        ], 200, );
       }
       else{
        return response()->json([
            'success'=>'error',
            'message'=>'Internal server error',
            'data'=>null
        ], 200, );
       }
}
}