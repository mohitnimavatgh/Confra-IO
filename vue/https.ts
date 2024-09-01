
import axios, { type AxiosResponse } from 'axios';
import qs from "qs";

axios.defaults.timeout = 50000;
axios.defaults.baseURL = `${import.meta.env.VITE_BASE_URL}/api/v1`;

axios.interceptors.request.use(
  (config: any) => {
    //@ts-ignore
    let token = localStorage.getItem("access_token")
    config.headers.Authorization = `Bearer ${token}`
    if (config.url!.startsWith("/") || config.method === 'delete') {
      config.headers['Content-Type'] = 'application/json , multipart/form-data';
    }
    return config;
  },
  error => {
    return Promise.reject(error);
  }
);

/**
 * @param url
 * @param data
 * @returns {Promise}
 */

export function post<T>(url: string, data: any = {}): Promise<T> {
  return new Promise((resolve, reject) => {
    const config = {
      method: 'post',
      url: `${axios.defaults.baseURL}${url}`,
      data: data
    };
    axios.request(config)
      .then((response: AxiosResponse<T>) => {
        resolve(response.data);
      })
      .catch(err => {
        reject(err);
      });
  });
}

/**
 * @param url
 * @param params
 * @returns {Promise}
 */
export function get<T>(url: string, params: any = {}): Promise<T> {
  return new Promise((resolve, reject) => {
    axios.get(url, {
      params,
      paramsSerializer(params) {
        return qs.stringify(params, { arrayFormat: 'repeat' });
      }
    })
      .then((response: AxiosResponse<T>) => {
        resolve(response.data);
      })
      .catch(err => {
        reject(err);
      });
  });
}
/**
 * @param url
 * @param data
 * @returns {Promise}
 */
export function put<T>(url: string, data: any = {}): Promise<T> {
  return new Promise((resolve, reject) => {
    const config = {
      method: 'put',
      url: `${axios.defaults.baseURL}${url}`,
      data: data
    };
    axios.request(config)
      .then((response: AxiosResponse<T>) => {
        resolve(response.data);
      })
      .catch(err => {
        reject(err);
      });
  });
}

/** *
* @param url
* @param data 
* @returns {Promise} 
*/
export function del<T>(url: string, data: any = {}): Promise<T> {
  const apiUrl = `${axios.defaults.baseURL}${url}`;

  return new Promise((resolve, reject) => {
    const config = {
      method: 'delete',
      url: apiUrl,
      data: data,
    };
    axios.request(config)
      .then((response: AxiosResponse<T>) => {
        resolve(response.data);
      })
      .catch(err => {
        reject(err);
      });
  });
}

/**
 * @param url
 * @param data
 * @returns {Promise}
 */
export function patch<T>(url: string, data: any = {}): Promise<T> {
  return new Promise((resolve, reject) => {
    const config = {
      method: 'patch',
      url: `${axios.defaults.baseURL}${url}`,
      data: data
    };
    axios.request(config)
      .then((response: AxiosResponse<T>) => {
        resolve(response.data);
      })
      .catch(err => {
        reject(err);
      });
  });
}