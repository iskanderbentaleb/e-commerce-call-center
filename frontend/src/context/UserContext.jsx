import { createContext, useContext, useState } from 'react';
import { guestApi } from './../services/api/guest/guestApi';

// inisial values context
export const UserStateContext = createContext({
    DahboardOpend: false , // this for the admin , seller dashborad when screen is small so we can close it 
    setDahboardOpend : ()=>{},
    user: '' ,
    setUser : ()=>{},
    logout: ()=>{},
    login: ()=>{},
    forgot_password: ()=>{},
    reset_password: ()=>{},
    setLanguage: ()=>{},
    authenticated: false ,
    setAuthenticated: ()=>{},
    setToken: ()=>{},
    setRefreshToken: ()=>{},
    setTokenSetTime: ()=>{},
});



export default function UserContext({children}){

    // tretment context values
    const [DahboardOpend, setDahboardOpend] = useState(false);
    const [user, setUser] = useState({});
    const [authenticated, _setauthenticated] = useState('true' === window.localStorage.getItem('AUTH'));

    const setTokenSetTime = (time) => {
        window.localStorage.setItem('TokenSetTime' , time)
    }

    const setRefreshToken = (RefreshToken) => {
        window.localStorage.setItem('RefreshToken' , RefreshToken)
    }

    const setToken  = (Token) => {
        window.localStorage.setItem('Token' , Token)
    }

    const setAuthenticated = (isAuth) => {
        _setauthenticated(isAuth)
        window.localStorage.setItem('AUTH' , isAuth)
    }


    const login = async (email,password) => {
        await guestApi.getCsrfToken()
        return await guestApi.login(email,password)
    }
    
    const forgot_password = async (email) => {
        await guestApi.getCsrfToken()
        return await guestApi.forgot_password(email)
    }
    
    const reset_password = async (token , email , password , password_confirmation ) => {
        await guestApi.getCsrfToken()
        return await guestApi.reset_password(token , email , password , password_confirmation)
    }

    const logout =  async () => {
        await guestApi.getCsrfToken()
        return await guestApi.logout();
    }


    // this to save language of backend 
    // when u change the front language change the backend 
    const setLocale = (locale) => {
        localStorage.setItem('locale', locale);
    }


    // share context with All App
    return (
        <>
           <UserStateContext.Provider value={{DahboardOpend , setDahboardOpend , user, setUser , logout , login , forgot_password , reset_password ,  authenticated , setAuthenticated , setToken , setRefreshToken , setTokenSetTime , setLocale}}>
                {children}
           </UserStateContext.Provider>
        </>
    )

}

// export to other component that we need
// => we use it like this : const { User , setUser } = useUserContext()  the we retrive data
export const useUserContext = () => useContext(UserStateContext); // it should be inside function

