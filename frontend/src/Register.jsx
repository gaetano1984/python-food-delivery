import React, { useState, Component } from "react";
import Button from "./Components/Button";
import FormGroup from "./Components/FormGroup";
import {z} from "zod";
import axios from "axios";
import {useNavigate} from 'react-router-dom';

//class Login extends Component{
export default function Register(){
    const navigate = useNavigate();
    const [form, setForm] = useState({
        username: "",
        password: "",
        name: "",
        lastname: "",
        errorUsername: "",
        errorPassword: "",
        errorName: "",
        errorLastname: ""
    });
    const handleChange = (e) => {
        //e.target.className.replace('border-red-300', 'border-grady-300');
        let {name, value} = e.target;
        setForm((prev) => ({
            ...prev,
            [name]: value
        }));
    };
    const register = (e) => {
        setForm((prev) => ({
            ...prev,
            errorUsername: "",
            errorPassword: "",
            errorLastname: "",
            errorName: ""
        }));
        try{
            const data = z.object({
                username: z.string().min(1, "Username is required"),
                password: z.string().min(1, "Password is required"),
                name: z.string().min(1, "Name is required"),
                lastname: z.string().min(1, "Last name is required")
            }).parse(form); 
            axios.post('/api/user/register', data)
                .then((success) => {              
                    alert(success.data.ok);
                    navigate('/login');
                })
                .catch((e) => {
                    console.log(e.response.data);
                    alert(e.response.data.detail);
                })
            ;
        }
        catch(e){
            if(e instanceof z.ZodError){
                e.issues.map((err) => {
                    if(err.path[0]==='username'){
                        setForm((prev) => ({
                            ...prev,
                            'errorUsername': err.message
                        }))
                    }
                    
                    if(err.path[0]==='password'){
                        setForm((prev) => ({
                            ...prev,
                            'errorPassword': err.message
                        }))
                    }

                    if(err.path[0]==='name'){
                        setForm((prev) => ({
                            ...prev,
                            'errorName': err.message
                        }))
                    }

                    if(err.path[0]==='lastname'){
                        setForm((prev) => ({
                            ...prev,
                            'errorLastname': err.message
                        }))
                    }
                });
            }
        }        
    };
    return (
        <div>
            <div className="flex items-center justify-center h-screen">
                <div className="w-full max-w-sm border border-gray-300 rounded-lg">
                    <div className="bg-blue-300 p-2 text-center font-semibold">
                        Register
                    </div>
                    <div className="grid grid-cols-1 gap-4 p-2">
                        <FormGroup label="username" type="input" name="username" options="" onChange={handleChange} error={form.errorUsername}></FormGroup>
                        <FormGroup label="password" type="password" name="password" options="" onChange={handleChange} error={form.errorPassword}></FormGroup>
                        <FormGroup label="name" type="input" name="name" options="" onChange={handleChange} error={form.errorName}></FormGroup>
                        <FormGroup label="lastname" type="input" name="lastname" options="" onChange={handleChange} error={form.errorLastname}></FormGroup>
                        <div className="p-4">
                            <div className="flex items-center gap-4 gap-">
                                <Button className="bg-blue-400 text-white p-4 pt-2 pb-2 rounded-4xl w-32 m-auto" text="Register" onClick={register}/>
                            </div>
                        </div>
                    </div>                    
                </div>
            </div>
        </div>
    )
}