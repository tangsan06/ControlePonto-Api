<?php
namespace Api\User;

require realpath('../../../../vendor/autoload.php');
include( '../../../../src/Helpers/headers.php' );
include( '../../../../src/Helpers/generic.php' );

try {
    if ( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
        try {
            $headers = apache_request_headers();
            $data = json_decode( file_get_contents( 'php://input' ) );
            $args = json_decode( file_get_contents( 'php://input' ), TRUE );

            if ( $data === null && json_last_error() !== JSON_ERROR_NONE ) {
                echo json_encode( [ ['status' => '403', 'info' => 'Payload Precondition Failed'] ] );
                die();
            }

            // # Verify Header Authorization Field
            // if ( !isset( $headers['Authorization'] ) ) {
            //     echo json_encode( [ ['status' => '402', 'info' => 'Invalid or Missing Token'] ] );

            //     die();
            // }
            
            if ( sizeof( $args ) != 2 ) {
                echo json_encode( [ ['status' => '202', 'info' => 'Invalid Arguments Number (Expected Two)'] ] );
                die();
            }

        } catch ( Exception $ex ) {
            echo json_encode( [ ['status:' => '400', 'info' => 'Bad Request (Invalid Syntax)'] ] );
            die();
        }

        # Loads: User and UserModel
        $user = new \Api\User\User();
        $userModel = new \Api\User\UserModel();

        // try {
        //     if ( !$userModel->auth( $headers['Authorization'] ) ) {
        //         echo json_encode( [ ['status' => '401', 'info' => 'Token Refused'] ] );
        //         die;
        //     }
        // } catch( \Exception $ex ) {
        //     echo json_encode( [ ['status' => '407', 'info' => $ex->getMessage()] ] );
        //     die;
        // }

        $err = [];
        try {
            ( !isset( $data->username ) ? array_push( $err, 1 ):NULL );
            ( !isset( $data->password ) ? array_push( $err, 1 ):NULL );

            if ( sizeof( $err ) > 0 ) {
                echo json_encode( [ ['status' => '403', 'info' => 'Payload Precondition Failed'] ] );
                die();
            }

        } catch ( \Exception $ex ) {
            echo json_encode( [ ['status' => '406', 'info' => 'Payload Precondition Failed'] ] );
            die;
        }

        try {
            $user->setUsername( strip_tags( $data->username ) );
            $user->setPassword( strip_tags( $data->password ) );
            $userId = $userModel->checkUser( $user )['id'];

            if ( $userId > 0 ) {
                $user->setId( $userId );
                                
                $user->setAuthToken(base64_encode($user->getUsername().":". getdate()[0]));
                
                if($userModel->updateToken($user) == true){
                    $search = array_merge( ['status' => "201"], $userModel->search( $user ) );
                    echo json_encode( [$search] );
                } else {
                    echo json_encode( [ ['status' => '406', 'info' => "Unexpected error" ] ] );

                }
 
            } else {
                echo json_encode( [ ['status' => '203', 'info' => 'User Not Found or Incorrect username and/or password'] ] );
            }
            die();

        } catch( \PDOException $e ) {
            echo json_encode( [ ['status' => '405', 'info' => SQLMessage( $e->getCode() ) ] ] );
        }

    } else {
        echo json_encode( [ ['status' => '404', 'info' => 'Method Not Allowed' ] ] );
    }
} catch( \Exception $ex ) {
    echo json_encode( [ ['status' => '406', 'info' => $ex->getMessage() ] ] );
    die();
}
