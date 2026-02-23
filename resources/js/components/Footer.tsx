// resources/js/components/Footer.tsx
import React from 'react';

const Footer = () => {
    return (
        <footer className="bg-white dark:bg-[#15361c] text-[#307051] dark:text-[#ffffff] py-4">
            <div className="container mx-auto">
                <hr className="border-t border-[#307051] dark:border-[#ffffff] mb-2" />
                <div className="flex justify-end text-sm">
                    <div className="text-right">
                        <p>
                            &copy; {new Date().getFullYear()} Lemif International Hotel and Resort. All rights reserved.
                        </p>
                        <nav>
                            <a href="/about" className="text-[#307051] hover:underline dark:hover:text-[#ffffff]">
                                Developed by Numan ICT
                            </a>
                        </nav>
                    </div>
                </div>
            </div>
        </footer>
    );
};

export default Footer;